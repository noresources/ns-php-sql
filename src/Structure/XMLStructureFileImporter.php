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
use NoreSources\SQL\Expression\Evaluator;
use NoreSources\SQL\Expression\Keyword;
use NoreSources\SQL\Expression\TokenizableExpression;
use NoreSources\SQL\Structure\XMLStructureFileConstants as K;

class XMLStructureFileImporter implements StructureFileImporterInterface
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

	public function importStructureFromDocument(\DOMDocument $document)
	{
		$context = new XMLStructureFileImporterContext($document);
		$namespaceURI = null;
		$validDocument = false;
		foreach ($context->xpath->query('namespace::*', $document->documentElement) as $node)
		{
			if (\strpos($node->nodeValue, K::XML_NAMESPACE_BASEURI) !== 0)
				continue;

			$namespaceURI = $node->nodeValue;

			$validDocument = true;
			$version = \trim(\trim(\substr($node->nodeValue, \strlen(K::XML_NAMESPACE_BASEURI))),
				'/');
			if (\strlen($version) == 0)
				$version = '1.0.0';

			$context->setSchemaVersion(new SemanticVersion($version), $namespaceURI);
		}

		$versionNumber = $context->schemaVersion->getIntegerValue();
		if (!$validDocument)
			throw new StructureException('Invalid XML document. Schema namespace not found');

		$name = $document->documentElement->getAttribute('name');
		if ($document->documentElement->localName == 'datasource')
		{
			$context->structureElement = new DatasourceStructure($name);
			$this->importDatasourceNode($document->documentElement, $context->structureElement,
				$context);
		}
		elseif ($document->documentElement->localName == 'tableset')
		{
			$context->structureElement = new TablesetStructure($name);
			$this->importTablesetNode($document->documentElement, $context->structureElement,
				$context);
		}
		elseif ($document->documentElement->localName == 'table')
		{
			$context->structureElement = new TableStructure($name);
			$this->importTableNode($document->documentElement, $context->structureElement, $context);
		}
		elseif ($document->documentElement->localName == 'column')
		{
			$context->structureElement = new ColumnStructure($name);
			$this->importColumnNode($document->documentElement, $context->structureElement, $context);
		}

		$this->importPostprocess($context);
		return $context->structureElement;
	}

	public function importDatasourceNode(\DOMNode $node, $structure,
		XMLStructureFileImporterContext $context)
	{
		if ($node->hasAttribute('id'))
			$context->identifiedElements->offsetSet($node->getAttribute('id'), $structure);

		$nodeName = K::XML_NAMESPACE_PREFIX . ':' .
			self::getXmlNodeName(TablesetStructure::class, $context->schemaVersion);
		$tablesetNodes = $context->xpath->query($nodeName);
		foreach ($tablesetNodes as $tablesetNode)
		{
			$tableset = new TablesetStructure($structure, $tablesetNode->getAttribute('name'));
			$structure->appendChild($tableset);
			$this->importTablesetNode($tablesetNode, $tableset, $context);
		}
	}

	public function importTablesetNode(\DOMNode $node, TablesetStructure $structure,
		XMLStructureFileImporterContext $context)
	{
		if ($node->hasAttribute('id'))
			$context->identifiedElements->offsetSet($node->getAttribute('id'), $structure);

		$nodeName = K::XML_NAMESPACE_PREFIX . ':' .
			self::getXmlNodeName(TableStructure::class, $context->schemaVersion);
		$tableNodes = $context->xpath->query($nodeName, $node);

		foreach ($tableNodes as $tableNode)
		{
			$table = new TableStructure($structure, $tableNode->getAttribute('name'));
			$structure->appendChild($table);
			$this->importTableNode($tableNode, $table, $context);
		}
	}

	public function importTableNode(\DOMNode $node, TableStructure $structure,
		XMLStructureFileImporterContext $context)
	{
		if ($node->hasAttribute('id'))
			$context->identifiedElements->offsetSet($node->getAttribute('id'), $structure);

		$columnNodeName = K::XML_NAMESPACE_PREFIX . ':' .
			self::getXmlNodeName(ColumnStructure::class, $context->schemaVersion);
		$columnNodes = $context->xpath->query($columnNodeName, $node);
		foreach ($columnNodes as $columnNode)
		{
			$column = new ColumnStructure($structure, $columnNode->getAttribute('name'));
			$structure->appendChild($column);
			$this->importColumnNode($columnNode, $column, $context);
		}

		$pkNode = self::getSingleElementByTagName($context->namespaceURI, $node, 'primarykey');
		if ($pkNode instanceof \DOMElement)
		{
			$constraint = new PrimaryKeyTableConstraint();
			$constraint->constraintName = $pkNode->getAttribute('name');

			$columnNodes = $context->xpath->query($columnNodeName, $pkNode);
			foreach ($columnNodes as $columnNode)
			{
				$name = $columnNode->getAttribute('name');
				if (!$structure->offsetExists($name))
				{
					throw new StructureException('Invalid primary column "' . $name . '"',
						$structure);
				}
				$constraint->append($structure->offsetGet($name));
			}

			$structure->addConstraint($constraint);
		}

		$fkNodes = $node->getElementsByTagNameNS($context->namespaceURI, 'foreignkey');
		foreach ($fkNodes as $fkNode)
		{
			$context->foreignKeys->append([
				'table' => $structure,
				'node' => $fkNode
			]);
		}
	}

	public function importColumnNode(\DOMNode $node, ColumnStructure $structure,
		XMLStructureFileImporterContext $context)
	{
		if ($node->hasAttribute('id'))
			$context->identifiedElements->offsetSet($node->getAttribute('id'), $structure);

		if ($context->schemaVersion->getIntegerValue() < 20000)
		{
			$notNullNode = self::getSingleElementByTagName($context->namespaceURI, $node, 'notnull');
			if ($notNullNode instanceof \DOMNode)
			{
				$flg = $structure->getColumnProperty(K::COLUMN_FLAGS);
				$structure->setColumnProperty(K::COLUMN_FLAGS, ($flg & ~K::COLUMN_FLAG_NULLABLE));
			}
		}

		$dataType = K::DATATYPE_UNDEFINED;
		$typeNode = null;

		$dataTypeNode = self::getSingleElementByTagName($context->namespaceURI, $node, 'datatype');
		if ($dataTypeNode instanceof \DOMElement)
		{
			if ($dataTypeNode->hasAttribute('nullable'))
			{
				$nullable = $dataTypeNode->getAttribute('nullable');
				$flg = $structure->getColumnProperty(K::COLUMN_FLAGS);
				if ($nullable == 'yes')
					$structure->setColumnProperty(K::COLUMN_FLAGS, ($flg | K::COLUMN_FLAG_NULLABLE));
				else
					$structure->setColumnProperty(K::COLUMN_FLAGS, ($flg & ~K::COLUMN_FLAG_NULLABLE));
			}

			$dataType = K::DATATYPE_UNDEFINED;
			foreach ($dataTypeNode->childNodes as $child)
			{
				$dataType = self::getDataTypeFromNodeName($child->localName, $context->schemaVersion);
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
				$dateNode = self::getSingleElementByTagName($context->namespaceURI, $typeNode,
					'date');
				$timeNode = self::getSingleElementByTagName($context->namespaceURI, $typeNode,
					'time');
				if ($dateNode instanceof \DOMElement || $timeNode instanceof \DOMElement)
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
			$scaleAttribute = ($context->schemaVersion->getIntegerValue() < 20000) ? 'decimals' : 'scale';
			$dataType = K::DATATYPE_INTEGER;
			if ($typeNode->hasAttribute('signed'))
			{
				$flg = $structure->getColumnProperty(K::COLUMN_FLAGS);
				$signed = $typeNode->getAttribute('signed');
				if ($signed == 'yes')
					$structure->setColumnProperty(K::COLUMN_FLAGS, $flg & ~K::COLUMN_FLAG_UNSIGNED);
				else
					$structure->setColumnProperty(K::COLUMN_FLAGS, $flg | K::COLUMN_FLAG_UNSIGNED);
			}
			if ($typeNode->hasAttribute('autoincrement'))
			{
				$flg = $structure->getColumnProperty(K::COLUMN_FLAGS);
				$structure->setColumnProperty(K::COLUMN_FLAGS, $flg | K::COLUMN_FLAG_AUTO_INCREMENT);
			}
			if ($typeNode->hasAttribute('length'))
			{
				$structure->setColumnProperty(K::COLUMN_LENGTH,
					intval($typeNode->getAttribute('length')));
			}
			if ($typeNode->hasAttribute($scaleAttribute))
			{
				$count = intval($typeNode->getAttribute($scaleAttribute));
				$structure->setColumnProperty(K::COLUMN_FRACTION_SCALE, $count);
				if ($count > 0)
				{
					$dataType = K::DATATYPE_FLOAT;
				}
			}
		}

		if ($dataType != K::DATATYPE_UNDEFINED)
			$structure->setColumnProperty(K::COLUMN_DATA_TYPE, $dataType);

		$defaultNode = self::getSingleElementByTagName($context->namespaceURI, $node, 'default');
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
				$defaultValueNode = self::getSingleElementByTagName($context->namespaceURI,
					$defaultNode, $name);
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
							$value = \DateTime::createFromFormat(\DateTime::ISO8601, $value);
						else
							$value = new Keyword(K::KEYWORD_CURRENT_TIMESTAMP);
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

				if (!($value instanceof TokenizableExpression))
					$value = Evaluator::literal($value, $defaultValueType);

				$structure->setColumnProperty(K::COLUMN_DEFAULT_VALUE, $value);

				break;
			} // for each default value type
		} // default node
	}

	private static function importPostprocess(XMLStructureFileImporterContext $context)
	{
		$resolver = new StructureResolver(null);

		$columnNodeName = K::XML_NAMESPACE_PREFIX . ':' .
			self::getXmlNodeName(ColumnStructure::class, $context->schemaVersion);

		foreach ($context->foreignKeys as $entry)
		{
			$structure = $entry['table'];
			$fkNode = $entry['node'];
			$resolver->setPivot($context->structureElement);

			$referenceNode = self::getSingleElementByTagName($context->namespaceURI, $fkNode,
				'reference', true);
			$columnNodes = $context->xpath->query($columnNodeName, $fkNode);

			$referenceTableNode = self::getSingleElementByTagName($context->namespaceURI,
				$referenceNode, 'tableref', true);
			$foreignTable = null;

			if ($referenceTableNode->hasAttribute('id'))
			{
				$id = $referenceTableNode->getAttribute('id');
				if (!$context->identifiedElements->offsetExists($id))
					throw new StructureException('Invalid table identifier ' . $id, $structure);

				$foreignTable = $context->identifiedElements->offsetGet($id);
			}
			elseif ($referenceTableNode->hasAttribute('name'))
			{
				$name = $referenceTableNode->getAttribute('name');
				$foreignTable = $resolver->findTable($name);
			}

			if (!($foreignTable instanceof TableStructure))
				throw new StructureException('Invalid foreign key reference table');

			$foreignColumnNodes = $context->xpath->query($columnNodeName, $referenceNode);

			$fk = new ForeignKeyTableConstraint($foreignTable);
			if ($fkNode->hasAttribute('name'))
				$fk->constraintName = $fkNode->getAttribute('name');

			for ($i = 0; $i < $columnNodes->length; $i++)
			{
				$columnNode = $columnNodes->item($i);
				$foreignColumnNode = $foreignColumnNodes->item($i);

				$name = $columnNode->getAttribute('name');
				$foreignColumnName = $foreignColumnNode->getAttribute('name');

				if (!$structure->offsetExists($name))
					throw new StructureException('Invalid foreign key column "' . $name . '"',
						$structure);

				if (!$foreignTable->offsetExists($foreignColumnName))
					throw new StructureException(
						'Invalid foreign key column "' . $foreignColumnName . '"', $foreignTable);

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
				$actionsNode = self::getSingleElementByTagName($context->namespaceURI, $fkNode,
					'actions');
			}

			if ($actionsNode)
			{
				foreach ($events as $event)
				{
					$eventNode = self::getSingleElementByTagName($context->namespaceURI,
						$actionsNode, strtolower($event));
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
		$this->identifiedElements = new \ArrayObject();
	}

	public function setSchemaVersion(SemanticVersion $version, $namespaceURI)
	{
		$this->schemaVersion = $version;
		$this->namespaceURI = $namespaceURI;
		$this->xpath->registerNamespace(K::XML_NAMESPACE_PREFIX, $namespaceURI);
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

	public $identifiedElements;
}