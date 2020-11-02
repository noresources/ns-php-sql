<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

use NoreSources\Container;
use NoreSources\SemanticVersion;
use NoreSources\SQL\Expression\ExpressionHelper;
use NoreSources\SQL\Expression\Keyword;
use NoreSources\SQL\Expression\Literal;
use NoreSources\SQL\Expression\TokenizableExpressionInterface;
use NoreSources\SQL\Structure\XMLStructureFileConstants as K;

/**
 * ns-xml SQL schema definition file importer
 */
class XMLStructureFileImporter implements
	StructureFileImporterInterface
{

	use XMLStructureFileTrait;

	public function __construct()
	{}

	public function importStructureFromFile($filename)
	{
		$document = new \DOMDocument('1.0', 'utf-8');
		$document->load($filename, LIBXML_XINCLUDE);
		$document->xinclude();

		return $this->importStructureFromDocument($document);
	}

	/**
	 *
	 * @param \DOMDocument $document
	 * @throws StructureException
	 * @return \NoreSources\SQL\Structure\StructureElement
	 */
	public function importStructureFromDocument(\DOMDocument $document)
	{
		$context = new XMLStructureFileImporterContext($document);
		$namespaceURI = null;
		$validDocument = false;
		foreach ($context->xpath->query('namespace::*',
			$document->documentElement) as $node)
		{
			if (\strpos($node->nodeValue, K::XML_NAMESPACE_BASEURI) !== 0)
				continue;

			$namespaceURI = $node->nodeValue;

			$validDocument = true;
			$version = \trim(
				\trim(
					\substr($node->nodeValue,
						\strlen(K::XML_NAMESPACE_BASEURI))), '/');
			if (\strlen($version) == 0)
				$version = '1.0.0';

			$context->setSchemaVersion(new SemanticVersion($version),
				$namespaceURI);
		}

		$versionNumber = $context->schemaVersion->getIntegerValue();
		if (!$validDocument)
			throw new StructureSerializationException(
				'Invalid XML document. Schema namespace not found');

		$name = $document->documentElement->getAttribute('name');
		if ($document->documentElement->localName == 'datasource')
		{
			$context->structureElement = new DatasourceStructure($name);
			$this->importDatasourceNode($document->documentElement,
				$context->structureElement, $context);
		}
		elseif ($document->documentElement->localName == 'namespace')
		{
			$context->structureElement = new NamespaceStructure($name);
			$this->importNamespaceNode($document->documentElement,
				$context->structureElement, $context);
		}
		elseif ($document->documentElement->localName == 'table')
		{
			$context->structureElement = new TableStructure($name);
			$this->importTableNode($document->documentElement,
				$context->structureElement, $context);
		}
		elseif ($document->documentElement->localName == 'column')
		{
			$context->structureElement = new ColumnStructure($name);
			$this->importColumnNode($document->documentElement,
				$context->structureElement, $context);
		}

		$this->importPostprocess($context);
		return $context->structureElement;
	}

	public function importDatasourceNode(\DOMNode $node, $structure,
		XMLStructureFileImporterContext $context)
	{
		if ($node->hasAttribute('id'))
			$context->identifiedElements->offsetSet(
				$node->getAttribute('id'), $structure);

		$nodeName = K::XML_NAMESPACE_PREFIX . ':' .
			self::getXmlNodeName(NamespaceStructure::class,
				$context->schemaVersion);
		$namespaceNodes = $context->xpath->query($nodeName);
		foreach ($namespaceNodes as $namespaceNode)
		{
			$namespace = new NamespaceStructure(
				$namespaceNode->getAttribute('name'), $structure);
			$structure->appendElement($namespace);
			$this->importNamespaceNode($namespaceNode, $namespace,
				$context);
		}
	}

	public function importNamespaceNode(\DOMNode $node,
		NamespaceStructure $structure,
		XMLStructureFileImporterContext $context)
	{
		if ($node->hasAttribute('id'))
			$context->identifiedElements->offsetSet(
				$node->getAttribute('id'), $structure);

		$tableNodeName = K::XML_NAMESPACE_PREFIX . ':' .
			self::getXmlNodeName(TableStructure::class,
				$context->schemaVersion);
		$tableNodes = $context->xpath->query($tableNodeName, $node);

		foreach ($tableNodes as $tableNode)
		{
			$table = new TableStructure(
				$tableNode->getAttribute('name'), $structure);
			$structure->appendElement($table);
			$this->importTableNode($tableNode, $table, $context);
		}

		$indexNodeName = K::XML_NAMESPACE_PREFIX . ':' .
			self::getXmlNodeName(IndexStructure::class,
				$context->schemaVersion);
		$indexNodes = $context->xpath->query($indexNodeName, $node);

		foreach ($indexNodes as $indexNode)
		{
			$index = new IndexStructure(
				$indexNode->getAttribute('name'), $structure);
			$structure->appendElement($index);
			$this->importIndexNode($indexNode, $index, $context);
		}
	}

	public function importIndexNode(\DOMNode $node,
		IndexStructure $structure,
		XMLStructureFileImporterContext $context)
	{
		if ($node->hasAttribute('id'))
			$context->identifiedElements->offsetSet(
				$node->getAttribute('id'), $structure);

		$flags = 0;

		if ($node->hasAttribute('unique') &&
			$node->getAttribute('unique') == 'yes')
		{
			$flags |= IndexStructure::UNIQUE;
		}

		$structure->setIndexFlags($flags);

		$context->indexes->append(
			[
				'structure' => $structure,
				'node' => $node
			]);
	}

	public function importTableNode(\DOMNode $node,
		TableStructure $structure,
		XMLStructureFileImporterContext $context)
	{
		if ($node->hasAttribute('id'))
			$context->identifiedElements->offsetSet(
				$node->getAttribute('id'), $structure);

		$columnNodeName = K::XML_NAMESPACE_PREFIX . ':' .
			self::getXmlNodeName(ColumnStructure::class,
				$context->schemaVersion);
		$columnNodes = $context->xpath->query($columnNodeName, $node);
		foreach ($columnNodes as $columnNode)
		{
			$column = new ColumnStructure(
				$columnNode->getAttribute('name'), $structure);
			$structure->appendElement($column);
			$this->importColumnNode($columnNode, $column, $context);
		}

		$pkNode = self::getSingleElementByTagName(
			$context->namespaceURI, $node, 'primarykey');
		if ($pkNode instanceof \DOMElement)
		{
			$constraint = new PrimaryKeyTableConstraint();
			$constraint->setName($pkNode->getAttribute('name'));

			$columnNodes = $context->xpath->query($columnNodeName,
				$pkNode);
			foreach ($columnNodes as $columnNode)
			{
				$name = $columnNode->getAttribute('name');
				if (!$structure->offsetExists($name))
				{
					throw new StructureException(
						'Invalid primary column "' . $name . '"',
						$structure);
				}
				$constraint->append($structure->offsetGet($name));
			}

			$structure->addConstraint($constraint);
		}

		$fkNodes = $node->getElementsByTagNameNS($context->namespaceURI,
			'foreignkey');
		foreach ($fkNodes as $fkNode)
		{
			$context->foreignKeys->append(
				[
					'table' => $structure,
					'node' => $fkNode
				]);
		}
	}

	public function importColumnNode(\DOMNode $node,
		ColumnStructure $structure,
		XMLStructureFileImporterContext $context)
	{
		$isNullable = true;

		if ($node->hasAttribute('id'))
			$context->identifiedElements->offsetSet(
				$node->getAttribute('id'), $structure);

		if ($context->schemaVersion->getIntegerValue() < 20000)
		{
			$notNullNode = self::getSingleElementByTagName(
				$context->namespaceURI, $node, 'notnull');
			if ($notNullNode instanceof \DOMNode)
				$isNullable = false;
		}

		$dataType = K::DATATYPE_UNDEFINED;
		$typeNode = null;

		$dataTypeNode = self::getSingleElementByTagName(
			$context->namespaceURI, $node, 'datatype');

		if ($dataTypeNode instanceof \DOMElement)
		{
			if ($dataTypeNode->hasAttribute('nullable'))
			{
				$nullable = $dataTypeNode->getAttribute('nullable');
				$isNullable = ($nullable == 'yes');
			}

			$dataType = K::DATATYPE_UNDEFINED;
			foreach ($dataTypeNode->childNodes as $child)
			{
				$dataType = self::getDataTypeFromNodeName(
					$child->localName, $context->schemaVersion);
				if ($dataType != K::DATATYPE_UNDEFINED)
				{
					$typeNode = $child;
					break;
				}
			}
		}

		if ($dataType & K::DATATYPE_TIMESTAMP)
		{
			if ($context->schemaVersion->getIntegerValue() < 20000)
			{
				if (!$typeNode->hasAttribute('timezone'))
					$dataType &= ~K::DATATYPE_TIMEZONE;

				if ($typeNode->hasAttribute('type'))
				{
					$timestampType = $typeNode->getAttribute('type');
					$dataType &= ~K::DATATYPE_DATETIME;
					if ($timestampType == 'date')
						$dataType |= K::DATATYPE_DATE;
					elseif ($timestampType == 'time')
						$dataType |= K::DATATYPE_TIME;
					elseif ($timestampType == 'datetime')
						$dataType |= K::DATATYPE_DATETIME;
				}
			}
			else
			{
				$dateNode = self::getSingleElementByTagName(
					$context->namespaceURI, $typeNode, 'date');
				$timeNode = self::getSingleElementByTagName(
					$context->namespaceURI, $typeNode, 'time');
				if ($dateNode instanceof \DOMElement ||
					$timeNode instanceof \DOMElement)
					$dataType = 0;

				if ($dateNode instanceof \DOMElement)
					$dataType |= K::DATATYPE_DATE;
				if ($timeNode instanceof \DOMElement)
				{
					$dataType |= K::DATATYPE_TIME;
					if ($timeNode->hasAttribute('timezone'))
						$dataType |= K::DATATYPE_TIMEZONE;
				}
			}
		}
		elseif ($dataType & K::DATATYPE_NUMBER)
		{
			$scaleAttribute = ($context->schemaVersion->getIntegerValue() <
				20000) ? 'decimals' : 'scale';
			$dataType = K::DATATYPE_INTEGER;
			if ($typeNode->hasAttribute('signed'))
			{
				$flg = $structure->getColumnProperty(K::COLUMN_FLAGS);
				$signed = $typeNode->getAttribute('signed');
				if ($signed == 'yes')
					$structure->setColumnProperty(K::COLUMN_FLAGS,
						$flg & ~K::COLUMN_FLAG_UNSIGNED);
				else
					$structure->setColumnProperty(K::COLUMN_FLAGS,
						$flg | K::COLUMN_FLAG_UNSIGNED);
			}
			if ($typeNode->hasAttribute('autoincrement'))
			{
				$flg = $structure->getColumnProperty(K::COLUMN_FLAGS);
				$structure->setColumnProperty(K::COLUMN_FLAGS,
					$flg | K::COLUMN_FLAG_AUTO_INCREMENT);
			}
			if ($typeNode->hasAttribute('length'))
			{
				$structure->setColumnProperty(K::COLUMN_LENGTH,
					intval($typeNode->getAttribute('length')));
			}
			if ($typeNode->hasAttribute($scaleAttribute))
			{
				$count = intval(
					$typeNode->getAttribute($scaleAttribute));
				$structure->setColumnProperty(K::COLUMN_FRACTION_SCALE,
					$count);
				if ($count > 0)
				{
					$dataType = K::DATATYPE_FLOAT;
				}
			}
		}
		elseif ($dataType == K::DATATYPE_STRING &&
			($typeNode instanceof \DOMNode))
		{
			$enumerationNode = self::getSingleElementByTagName(
				$context->namespaceURI, $typeNode, 'enumeration');
			if ($enumerationNode instanceof \DOMNode)
			{
				$values = [];
				$valueNodes = $context->xpath->query(
					K::XML_NAMESPACE_PREFIX . ':value', $enumerationNode);
				foreach ($valueNodes as $valueNode)
					$values[] = new Literal($valueNode->nodeValue,
						K::DATATYPE_STRING);

				$structure->setColumnProperty(K::COLUMN_ENUMERATION,
					$values);
			}
		}

		if ($isNullable)
			$dataType |= K::DATATYPE_NULL;

		if ($dataType != K::DATATYPE_UNDEFINED)
		{
			if ($dataType == K::DATATYPE_NULL)
				$dataType |= K::DATATYPE_STRING;
			$structure->setColumnProperty(K::COLUMN_DATA_TYPE, $dataType);
		}

		$defaultNode = self::getSingleElementByTagName(
			$context->namespaceURI, $node, 'default');
		if ($defaultNode instanceof \DOMElement)
		{
			$nodeNames = [
				'integer',
				'boolean',
				'string',
				'null',
				'number',
				'base64Binary',
				'hexBinary',
				'datetime',
				'timestamp'
			];

			foreach ($nodeNames as $name)
			{
				$defaultValueNode = self::getSingleElementByTagName(
					$context->namespaceURI, $defaultNode, $name);
				if (!($defaultValueNode instanceof \DOMNode))
					continue;

				$value = $defaultValueNode->nodeValue;
				$defaultValueType = K::DATATYPE_UNDEFINED;
				switch ($name)
				{
					case 'integer':
						$value = intval($value);
						$defaultValueType = K::DATATYPE_INTEGER;
					break;
					case 'boolean':
						$value = ($value == 'true' ? true : false);
						$defaultValueType = K::DATATYPE_BOOLEAN;
					break;
					case 'timestamp':
					case 'datetime':
						$defaultValueType = K::DATATYPE_TIMESTAMP;
						if (\strlen($value))
							$value = \DateTime::createFromFormat(
								\DateTime::ISO8601, $value);
						else
							$value = new Keyword(
								K::KEYWORD_CURRENT_TIMESTAMP);
					break;
					case 'null':
						$defaultValueType = K::DATATYPE_NULL;
						$value = null;
					break;
					case 'number':
						$ivalue = intval($value);
						$value = floatval($value);
						$defaultValueType = K::DATATYPE_FLOAT;
						if ($ivalue == $value)
						{
							$defaultValueType = K::DATATYPE_INTEGER;
							$value = $ivalue;
						}
					break;
					case 'base64Binary':
						$defaultValueType = K::DATATYPE_BINARY;
						$value = base64_decode($value);
					break;
					case 'hexBinary':
						$defaultValueType = K::DATATYPE_BINARY;
						$value = hex2bin($value);
					break;
					default:
					break;
				}

				if (!($value instanceof TokenizableExpressionInterface))
					$value = ExpressionHelper::literal($value,
						$defaultValueType);

				$structure->setColumnProperty(K::COLUMN_DEFAULT_VALUE,
					$value);

				break;
			} // for each default value type
		} // default node
	}

	private static function importPostprocess(
		XMLStructureFileImporterContext $context)
	{
		$resolver = new StructureResolver(null);

		$columnNodeName = K::XML_NAMESPACE_PREFIX . ':' .
			self::getXmlNodeName(ColumnStructure::class,
				$context->schemaVersion);

		foreach ($context->indexes as $entry)
		{
			/**
			 *
			 * @var IndexStructure $structure
			 */
			$structure = $entry['structure'];
			$node = $entry['node'];
			$pivot = $context->structureElement;
			$parent = $structure->getParentElement();
			if ($parent instanceof StructureElementInterface)
				$pivot = $parent;
			$resolver->setPivot($pivot);

			$columnNodes = $context->xpath->query($columnNodeName, $node);

			$referenceTableNode = self::getSingleElementByTagName(
				$context->namespaceURI, $node, 'tableref', true);

			$table = null;
			if ($referenceTableNode->hasAttribute('id'))
			{
				$id = $referenceTableNode->getAttribute('id');
				if (!$context->identifiedElements->offsetExists($id))
					throw new StructureException(
						'Invalid table identifier ' . $id, $structure);

				$table = $context->identifiedElements->offsetGet($id);
			}
			elseif ($referenceTableNode->hasAttribute('name'))
			{
				$name = $referenceTableNode->getAttribute('name');
				$table = $resolver->findTable($name);
			}

			$structure->setIndexTable($table);
			$resolver->setPivot($table);

			foreach ($columnNodes as $columnNode)
			{
				$structure->addIndexColumn(
					$resolver->findColumn(
						$columnNode->getAttribute('name')));
			}
		}

		foreach ($context->foreignKeys as $entry)
		{
			$structure = $entry['table'];
			$fkNode = $entry['node'];
			$resolver->setPivot($context->structureElement);

			$referenceNode = self::getSingleElementByTagName(
				$context->namespaceURI, $fkNode, 'reference', true);
			$columnNodes = $context->xpath->query($columnNodeName,
				$fkNode);

			$referenceTableNode = self::getSingleElementByTagName(
				$context->namespaceURI, $referenceNode, 'tableref', true);
			$foreignTable = null;

			if ($referenceTableNode->hasAttribute('id'))
			{
				$id = $referenceTableNode->getAttribute('id');
				if (!$context->identifiedElements->offsetExists($id))
					throw new StructureException(
						'Invalid table identifier ' . $id, $structure);

				$foreignTable = $context->identifiedElements->offsetGet(
					$id);
			}
			elseif ($referenceTableNode->hasAttribute('name'))
			{
				$name = $referenceTableNode->getAttribute('name');
				$foreignTable = $resolver->findTable($name);
			}

			if (!($foreignTable instanceof TableStructure))
				throw new StructureException(
					'Invalid foreign key reference table', $structure);

			$foreignColumnNodes = $context->xpath->query(
				$columnNodeName, $referenceNode);

			$fk = new ForeignKeyTableConstraint($foreignTable);
			if ($fkNode->hasAttribute('name'))
				$fk->setName($fkNode->getAttribute('name'));

			for ($i = 0; $i < $columnNodes->length; $i++)
			{
				$columnNode = $columnNodes->item($i);
				$foreignColumnNode = $foreignColumnNodes->item($i);

				$name = $columnNode->getAttribute('name');
				$foreignColumnName = $foreignColumnNode->getAttribute(
					'name');

				if (!$structure->offsetExists($name))
					throw new StructureException(
						'Invalid foreign key column "' . $name . '"',
						$structure);

				if (!$foreignTable->offsetExists($foreignColumnName))
					throw new StructureException(
						'Invalid foreign key column "' .
						$foreignColumnName . '"', $foreignTable);

				$fk->addColumn($name, $foreignColumnName);
			}

			$events = [
				'onUpdate',
				'onDelete'
			];

			$actions = [
				'cascade' => K::FOREIGN_KEY_ACTION_CASCADE,
				'restrict' => K::FOREIGN_KEY_ACTION_RESTRICT,
				'default' => K::FOREIGN_KEY_ACTION_SET_DEFAULT,
				'null' => K::FOREIGN_KEY_ACTION_SET_NULL
			];

			$actionsNode = $referenceNode;
			if ($context->schemaVersion->getIntegerValue() >= 20000)
			{
				$actionsNode = self::getSingleElementByTagName(
					$context->namespaceURI, $fkNode, 'actions');
			}

			if ($actionsNode)
			{
				foreach ($events as $event)
				{
					$eventNode = self::getSingleElementByTagName(
						$context->namespaceURI, $actionsNode,
						strtolower($event));
					if ($eventNode)
					{
						$action = $eventNode->getAttribute('action');
						if (Container::keyExists($actions, $action))
							$fk->$event = $actions[$action];
					}
				}
			}

			$structure->addConstraint($fk);
		}
	}
}

class XMLStructureFileImporterContext
{

	public function __construct(\DOMDocument $document)
	{
		$this->document = $document;
		$this->xpath = new \DOMXPath($document);
		$this->foreignKeys = new \ArrayObject();
		$this->indexes = new \ArrayObject();
		$this->identifiedElements = new \ArrayObject();
	}

	public function setSchemaVersion(SemanticVersion $version,
		$namespaceURI)
	{
		$this->schemaVersion = $version;
		$this->namespaceURI = $namespaceURI;
		$this->xpath->registerNamespace(K::XML_NAMESPACE_PREFIX,
			$namespaceURI);
	}

	/**
	 *
	 * @var \DOMDocument
	 */
	public $document;

	/**
	 * |var \DOMXPath
	 */
	public $xpath;

	/**
	 *
	 * @var SemanticVersion
	 */
	public $schemaVersion;

	public $namespaceURI;

	/**
	 *
	 * @var StructureElement
	 */
	public $structureElement;

	/**
	 *
	 * @var \ArrayObject
	 */
	public $foreignKeys;

	/**
	 *
	 * @var \ArrayObject
	 */
	public $indexes;

	public $identifiedElements;
}