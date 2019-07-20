<?php

namespace NoreSources\SQL;

use NoreSources as ns;
use NoreSources\SQL\Constants as K;

abstract class StructureSerializer implements \Serializable
{

	public function __construct(StructureElement $element = null)
	{
		$this->structureElement = $element;
	}

	/**
	 * @property-read StructureElement $structureElement
	 * @param string $member
	 * @throws \InvalidArgumentException
	 * @return \NoreSources\SQL\StructureElement
	 */
	public function __get($member)
	{
		if ($member == 'structureElement')
		{
			return $this->structureElement;
		}

		throw new \InvalidArgumentException($member);
	}

	/**
	 * @var StructureElement
	 */
	protected $structureElement;
}

class JSONStructureSerializer extends StructureSerializer
{

	/**
	 * @var integer
	 */
	public $jsonSerializeFlags;
	
	public function __construct(StructureElement $structure, $flags = 0)
	{
		parent::__construct($structure);
		$this->jsonSerializeFlags = $flags;
	}

	public function unserialize($serialized)
	{
		$json = json_decode($serialized);
		if (!is_object($json))
			throw new StructureException('Invalid JSON data');

		throw new \Exception('Not implemented');
	}

	public function serialize()
	{
		$data = null;
		if ($this->structureElement instanceof DatasourceStructure)
		{
			$data = $this->serializeDatasource($this->structureElement);		
		}
		elseif ($this->structureElement instanceof TableSetStructure)
		{
			$data = $this->serializeTableSet($this->structureElement);
		}
		elseif ($this->structureElement instanceof TableStructure)
		{
			$data = $this->serializeTable($this->structureElement);
		}
		elseif ($this->structureElement instanceof TableColumnStructure)
		{
			$data = $this->serializeTableColumn($this->structureElement);
		}
				
		return json_encode($data, $this->jsonSerializeFlags);
	}
	
	private function serializeDatasource(DatasourceStructure $structure)
	{
		$properties = array (
				'name' => $structure->getName(),
				'kind' => 'datasource',
				'tablesets' => array ()
		);
		
		foreach ($structure as $tableName => $table)
		{
			$properties['tablesets'][$tableName] = $this->serializeTableSet($table);
		}
		
		return $properties;
	}

	private function serializeTableSet(TableSetStructure $structure)
	{
		$properties = array (
				'tables' => array ()
		);
		
		foreach ($structure as $tableName => $table)
		{
			$properties['tables'][$tableName] = $this->serializeTable($table);
		}

		if (!($structure->parent() instanceof DatasourceStructure))
		{
			$properties = array_merge(array (
					'name' => $structure->getName(),
					'kind' => 'tableset'
			), $properties);
		}

		return $properties;
	}

	private function serializeTable(TableStructure $structure)
	{
		$properties = array (
				'columns' => array ()
		);

		foreach ($structure as $columnName => $column)
		{
			$properties['columns'][$columnName] = $this->serializeTableColumn($column);
		}

		if (!($structure->parent() instanceof TableSetStructure))
		{
			$properties = array_merge(array (
					'name' => $structure->getName(),
					'kind' => 'table'
			), $properties);
		}

		return $properties;
	}

	private function serializeTableColumn(TableColumnStructure $structure)
	{
		$properties = array ();
		foreach ($structure->getProperties() as $key => $property) {
			if ($property['set']) $properties[$key] = $property['value'];
		}
		if (!($structure->parent() instanceof TableStructure))
		{
			$properties = array_merge(array (
					'name' => $structure->getName(),
					'kind' => 'column'
			), $properties);
		}
		
		return $properties;
	}
}

class XMLStructureSerializer extends StructureSerializer
{

	public function __construct(StructureElement $element = null)
	{
		parent::__construct($element);
	}

	public function serialize()
	{
		if ($this->structureElement instanceof StructureElement)
			throw new StructureException('Nothing to serialize');

		$impl = new \DOMImplementation();
		$document = $impl->createDocument(K::XML_NAMESPACE_URI, self::XSLT_NAMESPACE_PREFIX . ':' . self::getXmlNodeName($this->structureElement));

		/**
		 * @todo
		 */

		return $document->saveXML();
	}

	public function unserialize($serialized)
	{
		$this->foreignKeys = new \ArrayObject();
		$this->identifiedElements = new \ArrayObject();
		$document = new \DOMDocument('1.0', 'utf-8');

		$document->loadXML($serialized);
		$document->xinclude();

		if ($document->documentElement->localName == K::XML_ELEMENT_DATASOURCE)
		{
			$this->structureElement = new DatasourceStructure($document->documentElement->getAttribute('name'));
			$this->unserializeDatasource($this->structureElement, $document->documentElement);
		}
		elseif ($document->documentElement->localName == K::XML_ELEMENT_TABLESET)
		{}
		elseif ($document->documentElement->localName == K::XML_ELEMENT_TABLE)
		{}
		elseif ($document->documentElement->localName == K::XML_ELEMENT_COLUMN)
		{}
	}

	private function unserializeDatasource(DatasourceStructure $structure, \DOMNode $node)
	{
		if ($node->hasAttribute('id'))
			$this->identifiedElements->offsetSet($node->getAttribute('id'), $structure);

		$xpath = new \DOMXPath($node->ownerDocument);
		$xpath->registerNamespace(K::XML_NAMESPACE_PREFIX, K::XML_NAMESPACE_URI);

		$nodeName = K::XML_NAMESPACE_PREFIX . ':' . K::XML_ELEMENT_TABLESET;
		$tablesetNodes = $xpath->query($nodeName);
		foreach ($tablesetNodes as $tablesetNode)
		{
			$tableset = new TableSetStructure($structure, $tablesetNode->getAttribute('name'));
			$structure->appendChild($tableset);
			$this->unserializeTableSet($tableset, $tablesetNode);
		}
	}

	private function unserializeTableSet(TableSetStructure $structure, \DOMNode $node)
	{
		if ($node->hasAttribute('id'))
			$this->identifiedElements->offsetSet($node->getAttribute('id'), $structure);

		$xpath = new \DOMXPath($node->ownerDocument);
		$xpath->registerNamespace(K::XML_NAMESPACE_PREFIX, K::XML_NAMESPACE_URI);

		$nodeName = K::XML_NAMESPACE_PREFIX . ':' . K::XML_ELEMENT_TABLE;
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
		$xpath->registerNamespace(K::XML_NAMESPACE_PREFIX, K::XML_NAMESPACE_URI);

		$columnNodeName = K::XML_NAMESPACE_PREFIX . ':' . K::XML_ELEMENT_COLUMN;
		$columnNodes = $xpath->query($columnNodeName, $node);
		foreach ($columnNodes as $columnNode)
		{
			$column = new TableColumnStructure($structure, $columnNode->getAttribute('name'));
			$structure->appendChild($column);
			$this->unserializeTableColumn($column, $columnNode);
		}

		$pkNode = self::getSingleElementByTagName($node, K::XML_ELEMENT_PRIMARY_KEY);
		if ($pkNode instanceof \DOMElement)
		{
			$constraint = new KeyTableConstraint(K::TABLE_CONSTRAINT_PRIMARY_KEY);
			$constraint->name = $pkNode->getAttribute('name');

			$columnNodes = $xpath->query($columnNodeName, $pkNode);
			foreach ($columnNodes as $columnNode)
			{
				$name = $columnName->getAttribute('name');
				if (!$structure->offsetExists($name))
				{
					throw new StructureException('Invalid primary column "' . $name . '"', $structure);
				}
				$constraint->offsetSet($name, $structure->offsetGet($name));
			}

			$structure->addConstraint($constraint);
		}

		$fkNodes = $node->getElementsByTagNameNS(K::XML_NAMESPACE_URI, K::XML_ELEMENT_FOREIGN_KEY);
		foreach ($fkNodes as $fkNode)
		{
			$this->foreignKeys->append(array (
					'table' => $structure,
					'node' => $fkNode
			));
		}
	}

	private function unserializeTableColumn(TableColumnStructure $structure, \DOMNode $node)
	{
		if ($node->hasAttribute('id'))
			$this->identifiedElements->offsetSet($node->getAttribute('id'), $structure);

		$type = K::kDataTypeUndefined;
		$typeNode = null;

		$dataTypeNode = self::getSingleElementByTagName($node, 'datatype');
		if ($dataTypeNode instanceof \DOMElement)
		{
			$a = array (
					'binary' => K::kDataTypeBinary,
					'boolean' => K::kDataTypeBoolean,
					'numeric' => K::kDataTypeNumber,
					'timestamp' => K::kDataTypeTimestamp,
					'string' => K::kDataTypeString
			);

			foreach ($a as $k => $v)
			{
				$typeNode = self::getSingleElementByTagName($dataTypeNode, $k);
				if ($typeNode instanceof \DOMElement)
				{
					$type = $v;
					break;
				}
			}
		}

		if ($type & K::kDataTypeNumber)
		{
			$type = K::kDataTypeInteger;
			if ($typeNode->hasAttribute('autoincrement'))
			{
				$structure->setProperty(K::PROPERTY_COLUMN_AUTOINCREMENT, true);
			}
			if ($typeNode->hasAttribute('length'))
			{
				$structure->setProperty(K::PROPERTY_COLUMN_DATA_SIZE, intval($typeNode->getAttribute('length')));
			}
			if ($typeNode->hasAttribute('decimals'))
			{
				$count = intval($typeNode->getAttribute('decimals'));
				$structure->setProperty(K::PROPERTY_COLUMN_DECIMAL_COUNT, $count);
				if ($count > 0)
				{
					$type = K::kDataTypeFloat;
				}
			}
		}

		if ($type != K::kDataTypeUndefined)
			$structure->setProperty(K::PROPERTY_COLUMN_DATA_TYPE, $type);

		$defaultNode = self::getSingleElementByTagName($node, 'default');
		if ($defaultNode instanceof \DOMElement)
		{
			$nodeNames = array (
					'integer',
					'boolean',
					'datetime',
					'string',
					'null',
					'number',
					'base64Binary',
					'hexBinary'
			);

			foreach ($nodeNames as $name)
			{
				$valueNode = self::getSingleElementByTagName($defaultNode, $name);
				if (!($valueNode instanceof \DOMNode))
					continue;

				$value = $valueNode->nodeValue;

				switch ($name)
				{
					case 'integer':
						$value = intval($value);
						break;
					case 'boolean':
						$value = ($value == 'true' ? true : false);
						break;
					case 'datetime':
						$value = \DateTime::createFromFormat(\DateTime::ISO8601, $value);
						break;
					case 'null':
						$value = null;
						break;
					case 'number':
						$value = floatval($value);
						break;
					case 'base64Binary':
						$value = base64_decode($value);
						break;
					case 'hexBinary':
						$value = hex2bin($value);
						break;
					default:
						break;
				}

				$structure->setProperty(K::PROPERTY_COLUMN_DEFAULT_VALUE, $value);

				break;
			} // for each default value type
		} // default node
	}

	public function getXmlNodeName(StructureElement $element)
	{
		if ($element instanceof DatasourceStructure)
			return K::XML_ELEMENT_DATASOURCE;
		elseif ($element instanceof TableSetStructure)
			return K::XML_ELEMENT_TABLESET;
		elseif ($element instanceof TableStructure)
			return K::XML_ELEMENT_TABLE;
		elseif ($element instanceof TableColumnStructure)
			return K::XML_ELEMENT_COLUMN;
		throw new \InvalidArgumentException();
	}

	private static function getSingleElementByTagName(\DOMElement $element, $localName, $required = false)
	{
		$list = $element->getElementsByTagNameNS(K::XML_NAMESPACE_URI, $localName);

		if ($list->length > 1)
			throw new StructureException('Invalid number of ' . $localName . ' nodes. At most 1 expected');
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
	 * @throws StructureException
	 */
	private function userializePostprocess()
	{
		$resolver = new StructureResolver(null);
		foreach ($this->foreignKeys as $entry)
		{
			$structure = $entry['table'];
			$fkNode = $entry['node'];
			$referenceNode = self::getSingleElementByTagName($fkNode, 'reference', true);
			$resolver->setPivot($structure);

			$columnNodes = $xpath->query($columnNodeName, $fkNode);
			$referenceColumnNodes = $xpath->query($columnNodeName, $referenceNode);
			if ($columnNodes->length != $referenceNodes->length)
			{
				throw new StructureException('Invalid foreign key', $structure);
			}

			$referenceTableNode = self::getSingleElementByTagName($referenceNode, 'tableref', true);
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

			$fk = new ForeignKeyTableConstraint($foreignTable);
			for ($i = 0; $i < $columnNode->length; $i++)
			{
				$columnNode = $columnNodes->item(0);
				$referenceNode = $referenceNodes->item(0);

				$name = $columnNode->getAttribute('name');
				$foreignColumnName = $referenceNode->getAttribute('name');
				if (!$structure->offsetExists($name))
				{
					throw new StructureException('Invalid foreign key column "' . $name . '"', $structure);
				}

				if (!$foreignTable->offsetExists($foreignColumnName))
				{
					throw new StructureException('Invalid foreign key column "' . $foreignColumnName . '"', $foreignTable);
				}
				
				$fk->addColumn($columnNode, $foreignColumnName);
			}
				
			$structure->addConstraint ($fk);
		}
	}
	
	/**
	 * @var \ArrayObject
	 */
	private $foreignKeys;
	private $identifiedElements;
}

