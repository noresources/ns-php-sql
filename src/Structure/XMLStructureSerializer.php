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

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Expression\Evaluator as X;
use NoreSources\SQL\Expression\Expression;
use NoreSources\SQL\Expression\Keyword;
use NoreSources as ns;

/**
 */
class XMLStructureSerializer extends StructureSerializer
{

	public function __construct(StructureElement $element = null)
	{
		parent::__construct($element);
		$this->schemaVersion = new ns\SemanticVersion('2.0.0');
		$this->schemaNamespaceURI = K::XML_NAMESPACE_BASEURI . '/2.0';
	}

	public function serialize()
	{
		if ($this->structureElement instanceof StructureElement)
			throw new StructureException('Nothing to serialize');

		$impl = new \DOMImplementation();
		$document = $impl->createDocument($this->schemaNamespaceURI,
			self::XSLT_NAMESPACE_PREFIX . ':' . self::getXmlNodeName($this->structureElement));

		/**
		 *
		 * @todo
		 */

		return $document->saveXML();
	}

	public function unserializeFromFile($filename)
	{
		$document = new \DOMDocument('1.0', 'utf-8');
		$document->load($filename, LIBXML_XINCLUDE);
		return $this->unserialize($document);
	}

	/**
	 *
	 * @param \DOMDocument|string $serialized
	 *        	Serialized structure
	 */
	public function unserialize($serialized)
	{
		$this->foreignKeys = new \ArrayObject();
		$this->identifiedElements = new \ArrayObject();

		$document = null;

		if ($serialized instanceof \DOMDocument)
			$document = $serialized;
		else
		{
			$document = new \DOMDocument('1.0', 'utf-8');
			$document->loadXML($serialized, LIBXML_XINCLUDE);
		}

		$document->xinclude();

		$xpath = new \DOMXPath($document);
		$validDocument = false;
		foreach ($xpath->query('namespace::*', $document->documentElement) as $node)
		{
			if (strpos($node->nodeValue, K::XML_NAMESPACE_BASEURI) === 0)
			{
				$this->schemaNamespaceURI = $node->nodeValue;

				$validDocument = true;
				$version = trim(trim(substr($node->nodeValue, strlen(K::XML_NAMESPACE_BASEURI))),
					'/');
				if (strlen($version) == 0)
					$version = '1.0.0';
				$this->schemaVersion = new ns\SemanticVersion($version);
			}
		}

		$versionNumber = $this->schemaVersion->getIntegerValue();

		if (!$validDocument)
			throw new StructureException('Invalid XML document. Schema namespace not found');

		if ($document->documentElement->localName == 'datasource')
		{
			$this->structureElement = new DatasourceStructure(
				$document->documentElement->getAttribute('name'));
			$this->unserializeDatasource($this->structureElement, $document->documentElement);
		}
		elseif (($schemaVersion < 20000 && $document->documentElement->localName == 'database') ||
			($schemaVersion >= 20000 && $document->documentElement->localName == 'tableset'))
		{
			$this->structureElement = new TablesetStructure(
				$document->documentElement->getAttribute('name'));
			$this->unserializeTableset($this->structureElement, $document->documentElement);
		}
		elseif ($document->documentElement->localName == 'table')
		{
			$this->structureElement = new TableStructure(
				$document->documentElement->getAttribute('name'));
			$this->unserializeTable($this->structureElement, $document->documentElement);
		}
		elseif ($document->documentElement->localName == 'column')
		{
			$this->structureElement = new ColumnStructure(
				$document->documentElement->getAttribute('name'));
			$this->unserializeTableColumn($this->structureElement, $document->documentElement);
		}

		$this->userializePostprocess($document);
	}

	private function unserializeDatasource(DatasourceStructure $structure, \DOMNode $node)
	{
		if ($node->hasAttribute('id'))
			$this->identifiedElements->offsetSet($node->getAttribute('id'), $structure);

		$xpath = new \DOMXPath($node->ownerDocument);
		$xpath->registerNamespace(K::XML_NAMESPACE_PREFIX, $this->schemaNamespaceURI);

		$nodeName = ($this->schemaVersion->getIntegerValue() < 20000) ? 'database' : 'tableset';
		$nodeName = K::XML_NAMESPACE_PREFIX . ':' . $nodeName;
		$tablesetNodes = $xpath->query($nodeName);
		foreach ($tablesetNodes as $tablesetNode)
		{
			$tableset = new TablesetStructure($structure, $tablesetNode->getAttribute('name'));
			$structure->appendChild($tableset);
			$this->unserializeTableset($tableset, $tablesetNode);
		}
	}

	private function unserializeTableset(TablesetStructure $structure, \DOMNode $node)
	{
		if ($node->hasAttribute('id'))
			$this->identifiedElements->offsetSet($node->getAttribute('id'), $structure);

		$xpath = new \DOMXPath($node->ownerDocument);
		$xpath->registerNamespace(K::XML_NAMESPACE_PREFIX, $this->schemaNamespaceURI);

		$nodeName = K::XML_NAMESPACE_PREFIX . ':' . 'table';
		$tableNodes = $xpath->query($nodeName, $node);
		foreach ($tableNodes as $tableNode)
		{
			$table = new TableStructure($structure, $tableNode->getAttribute('name'));
			$structure->appendChild($table);
			$this->unserializeTable($table, $tableNode);
		}
	}

	private function unserializeTable(TableStructure $structure, \DOMNode $node)
	{
		if ($node->hasAttribute('id'))
			$this->identifiedElements->offsetSet($node->getAttribute('id'), $structure);

		$xpath = new \DOMXPath($node->ownerDocument);
		$xpath->registerNamespace(K::XML_NAMESPACE_PREFIX, $this->schemaNamespaceURI);

		$columnNodeName = K::XML_NAMESPACE_PREFIX . ':' . 'column';
		$columnNodes = $xpath->query($columnNodeName, $node);
		foreach ($columnNodes as $columnNode)
		{
			$column = new ColumnStructure($structure, $columnNode->getAttribute('name'));
			$structure->appendChild($column);
			$this->unserializeTableColumn($column, $columnNode);
		}

		$pkNode = self::getSingleElementByTagName($this->schemaNamespaceURI, $node, 'primarykey');
		if ($pkNode instanceof \DOMElement)
		{
			$constraint = new PrimaryKeyTableConstraint();
			$constraint->constraintName = $pkNode->getAttribute('name');

			$columnNodes = $xpath->query($columnNodeName, $pkNode);
			foreach ($columnNodes as $columnNode)
			{
				$name = $columnNode->getAttribute('name');
				if (!$structure->offsetExists($name))
				{
					throw new StructureException('Invalid primary column "' . $name . '"',
						$structure);
				}
				$constraint->offsetSet($name, $structure->offsetGet($name));
			}

			$structure->addConstraint($constraint);
		}

		$fkNodes = $node->getElementsByTagNameNS($this->schemaNamespaceURI, 'foreignkey');
		foreach ($fkNodes as $fkNode)
		{
			$this->foreignKeys->append([
				'table' => $structure,
				'node' => $fkNode
			]);
		}
	}

	private function unserializeTableColumn(ColumnStructure $structure, \DOMNode $node)
	{
		if ($node->hasAttribute('id'))
			$this->identifiedElements->offsetSet($node->getAttribute('id'), $structure);

		$notNullNode = self::getSingleElementByTagName($this->schemaNamespaceURI, $node, 'notnull');
		if ($notNullNode instanceof \DOMNode)
		{
			$structure->setColumnProperty(K::COLUMN_PROPERTY_ACCEPT_NULL, false);
		}

		$type = K::DATATYPE_UNDEFINED;
		$typeNode = null;

		$dataTypeNode = self::getSingleElementByTagName($this->schemaNamespaceURI, $node, 'datatype');
		if ($dataTypeNode instanceof \DOMElement)
		{
			$a = [
				'binary' => K::DATATYPE_BINARY,
				'boolean' => K::DATATYPE_BOOLEAN,
				'numeric' => K::DATATYPE_NUMBER,
				'timestamp' => K::DATATYPE_TIMESTAMP,
				'string' => K::DATATYPE_STRING
			];

			foreach ($a as $k => $v)
			{
				$typeNode = self::getSingleElementByTagName($this->schemaNamespaceURI, $dataTypeNode,
					$k);
				if ($typeNode instanceof \DOMElement)
				{
					$type = $v;
					break;
				}
			}
		}

		if ($type & K::DATATYPE_TIMESTAMP)
		{
			if ($this->schemaVersion->getIntegerValue() < 20000)
			{
				if (!$typeNode->hasAttribute('timezone'))
				{
					$type &= ~K::DATATYPE_TIMEZONE;
				}

				if ($typeNode->hasAttribute('type'))
				{
					$timestampType = $typeNode->getAttribute('type');
					$type &= ~K::DATATYPE_DATETIME;
					if ($timestampType == 'date')
						$type |= K::DATATYPE_DATE;
					elseif ($timestampType == 'time')
						$type |= K::DATATYPE_TIME;
					elseif ($timestampType == 'datetime')
						$type |= K::DATATYPE_DATETIME;
				}
			}
			else
			{
				$dateNode = self::getSingleElementByTagName($this->schemaNamespaceURI, $typeNode,
					'date');
				$timeNode = self::getSingleElementByTagName($this->schemaNamespaceURI, $typeNode,
					'time');
				if ($dateNode instanceof \DOMElement || $timeNode instanceof \DOMElement)
				{
					$type = 0;
				}

				if ($dateNode instanceof \DOMElement)
					$type |= K::DATATYPE_DATE;
				if ($timeNode instanceof \DOMElement)
				{
					$type |= K::DATATYPE_TIME;
					if ($timeNode->hasAttribute('timezone'))
						$type |= K::DATATYPE_TIMEZONE;
				}
			}
		}
		elseif ($type & K::DATATYPE_NUMBER)
		{
			$type = K::DATATYPE_INTEGER;
			if ($typeNode->hasAttribute('autoincrement'))
			{
				$structure->setColumnProperty(K::COLUMN_PROPERTY_AUTO_INCREMENT, true);
			}
			if ($typeNode->hasAttribute('length'))
			{
				$structure->setColumnProperty(K::COLUMN_PROPERTY_DATA_LENGTH,
					intval($typeNode->getAttribute('length')));
			}
			if ($typeNode->hasAttribute('decimals'))
			{
				$count = intval($typeNode->getAttribute('decimals'));
				$structure->setColumnProperty(K::COLUMN_PROPERTY_SCALE, $count);
				if ($count > 0)
				{
					$type = K::DATATYPE_FLOAT;
				}
			}
		}

		if ($type != K::DATATYPE_UNDEFINED)
			$structure->setColumnProperty(K::COLUMN_PROPERTY_DATA_TYPE, $type);

		$defaultNode = self::getSingleElementByTagName($this->schemaNamespaceURI, $node, 'default');
		if ($defaultNode instanceof \DOMElement)
		{
			$nodeNames = [
				'integer',
				'boolean',
				'string',
				'null',
				'number',
				'base64Binary',
				'hexBinary'
			];

			if ($this->schemaVersion->getIntegerValue() < 20000)
				$nodeNames[] = 'datetime';
			else
			{
				$nodeNames[] = 'timestamp';
				$nodeNames[] = 'now';
			}

			foreach ($nodeNames as $name)
			{
				$valueNode = self::getSingleElementByTagName($this->schemaNamespaceURI, $defaultNode,
					$name);
				if (!($valueNode instanceof \DOMNode))
					continue;

				$value = $valueNode->nodeValue;
				$valueType = K::DATATYPE_UNDEFINED;
				switch ($name)
				{
					case 'integer':
						$value = intval($value);
						$valueType = K::DATATYPE_INTEGER;
					break;
					case 'boolean':
						$value = ($value == 'true' ? true : false);
						$valueType = K::DATATYPE_BOOLEAN;
					break;
					case 'timestamp':
					case 'now':
					case 'datetime':
						$valueType = K::DATATYPE_TIMESTAMP; // deprecated
						if (strlen($value))
							$value = \DateTime::createFromFormat(\DateTime::ISO8601, $value);
						else
							$value = new Keyword(K::KEYWORD_CURRENT_TIMESTAMP);
					break;
					case 'null':
						$valueType = K::DATATYPE_NULL;
						$value = null;
					break;
					case 'number':
						$ivalue = intval($value);
						$value = floatval($value);
						$valueType = K::DATATYPE_FLOAT;
						if ($ivalue == $value)
						{
							$valueType = K::DATATYPE_INTEGER;
							$value = $ivalue;
						}
					break;
					case 'base64Binary':
						$valueType = K::DATATYPE_BINARY;
						$value = base64_decode($value);
					break;
					case 'hexBinary':
						$valueType = K::DATATYPE_BINARY;
						$value = hex2bin($value);
					break;
					default:
					break;
				}

				if (!($value instanceof Expression))
				{
					$value = X::literal($value, $valueType);
				}

				$structure->setColumnProperty(K::COLUMN_PROPERTY_DEFAULT_VALUE, $value);

				break;
			} // for each default value type
		} // default node
	}

	public function getXmlNodeName(StructureElement $element)
	{
		if ($element instanceof DatasourceStructure)
			return 'datasource';
		elseif ($element instanceof TablesetStructure)
		{
			if ($this->schemaVersion->getIntegerValue() < 20000)
				return 'database';
			return 'tableset';
		}
		elseif ($element instanceof TableStructure)
			return 'table';
		elseif ($element instanceof ColumnStructure)
			return 'column';
		throw new \InvalidArgumentException();
	}

	private static function getSingleElementByTagName($namespace, \DOMElement $element, $localName,
		$required = false)
	{
		$list = $element->getElementsByTagNameNS($namespace, $localName);

		if ($list->length > 1)
			throw new StructureException(
				'Invalid number of ' . $localName . ' nodes. At most 1 expected');
		if ($list->length == 0)
		{
			if ($required)
				throw new StructureException($localName . ' not found');

			return null;
		}

		return $list->item(0);
	}

	/**
	 * Resolve foreign key constaints
	 *
	 * @throws StructureException
	 */
	private function userializePostprocess(\DOMDocument $document)
	{
		$resolver = new StructureResolver(null);

		$xpath = new \DOMXPath($document);
		$xpath->registerNamespace(K::XML_NAMESPACE_PREFIX, $this->schemaNamespaceURI);

		$columnNodeName = K::XML_NAMESPACE_PREFIX . ':' . 'column';

		foreach ($this->foreignKeys as $entry)
		{
			$structure = $entry['table'];
			$fkNode = $entry['node'];
			$resolver->setPivot($structure);

			$referenceNode = self::getSingleElementByTagName($this->schemaNamespaceURI, $fkNode,
				'reference', true);
			$columnNodes = $xpath->query($columnNodeName, $fkNode);

			$referenceTableNode = self::getSingleElementByTagName($this->schemaNamespaceURI,
				$referenceNode, 'tableref', true);
			$foreignTable = null;

			if ($referenceTableNode->hasAttribute('id'))
			{
				$id = $referenceTableNode->getAttribute('id');
				if (!$this->identifiedElements->offsetExists($id))
				{
					throw new StructureException('Invalid table identifier ' . $id, $structure);
				}

				$foreignTable = $this->identifiedElements->offsetGet($id);
			}
			elseif ($referenceTableNode->hasAttribute('name'))
			{
				$name = $referenceTableNode->getAttribute('name');
				$foreignTable = $resolver->findTable($name);
			}

			if (!($foreignTable instanceof TableStructure))
			{
				throw new StructureException('Invalid foreign key reference table');
			}

			$foreignColumnNodes = $xpath->query($columnNodeName, $referenceNode);

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
				{
					throw new StructureException('Invalid foreign key column "' . $name . '"',
						$structure);
				}

				if (!$foreignTable->offsetExists($foreignColumnName))
				{
					throw new StructureException(
						'Invalid foreign key column "' . $foreignColumnName . '"', $foreignTable);
				}

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
			if ($this->schemaVersion->getIntegerValue() >= 20000)
			{
				$actionsNode = self::getSingleElementByTagName($this->schemaNamespaceURI, $fkNode,
					'actions');
			}

			if ($actionsNode)
			{
				foreach ($events as $event)
				{
					$eventNode = self::getSingleElementByTagName($this->schemaNamespaceURI,
						$actionsNode, strtolower($event));
					if ($eventNode)
					{
						$action = $eventNode->getAttribute('action');
						if (ns\Container::keyExists($actions, $action))
						{
							$fk->$event = $actions[$action];
						}
					}
				}
			}

			$structure->addConstraint($fk);
		}
	}

	/**
	 *
	 * @var \ArrayObject
	 */
	private $foreignKeys;

	private $identifiedElements;

	/**
	 *
	 * @var \NoreSources\SemanticVersion
	 */
	private $schemaVersion;

	private $schemaNamespaceURI;
}

