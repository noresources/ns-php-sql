<?php

/**
 * Copyright © 2012-2018 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */

/**
 *
 * @package SQL
 */
namespace NoreSources\SQL;

use NoreSources as ns;

abstract class StructureElement implements \ArrayAccess, \IteratorAggregate, \Countable
{
	/**
	 * The type of Datasource column.
	 * Value type: integer
	 */
	const DATA_TYPE = 'datasourcetype';
	
	/**
	 * The column is part of a primary key.
	 * Value type: boolean
	 */
	const PRIMARY_KEY = 'primary';
	/**
	 * The column value is auto-incremented (integer column type only).
	 * Value type: boolean
	 */
	const AUTO_INCREMENT = 'auto';
	const FOREIGN_KEY = 'foreign';
	
	/**
	 * The column is indexed.
	 * Value type: boolean
	 */
	const INDEXED = 'index';
	
	/**
	 * The column accepts null values.
	 * Value type: boolean
	 */
	const ACCEPT_NULL = 'null';
	
	/**
	 * Data size.
	 *
	 * Value type: integer
	 */
	const DATA_SIZE = 'size';
	
	/**
	 * Number of decimals (numeric field types).
	 * Value type: integer
	 */
	const DECIMAL_COUNT = 'decimalsize';
	
	/**
	 * List of valid values.
	 * Value type: array
	 */
	const ENUMERATION = 'valid_values';
	
	/**
	 * Default value.
	 * Value type: mixed
	 */
	const DEFAULT_VALUE = 'default_value';
	
	/*
	 * @var string ns-xml SQL schema namespace
	 */
	const XMLNAMESPACE = 'http://xsd.nore.fr/sql';

	public static function create($input, StructureElement $parent = null, $postProcessElementCallback = null)
	{
		if (is_string($input) && is_file($input))
		{
			return self::createFromXmlFile($input, $postProcessElementCallback);
		}
		else if ($input instanceof \DOMNode)
		{
			return self::createFromXmlNode($input, $parent, $postProcessElementCallback);
		}
		
		return ns\Reporter::error(__CLASS__, __METHOD__ . ': Invalid call');
	}

	/**
	 *
	 * @param unknown $filename XML SQL structure to load
	 * @param unknown $postProcessElementCallback A delegate called for each node. The currently processed node
	 * @param string $xincludeSupport Resolve XInclude instructions
	 */
	public static function createFromXmlFile($filename, $postProcessElementCallback = null, $xincludeSupport = true)
	{
		$doc = new \DOMDocument();
		if (!$doc->load($filename))
		{
			return ns\Reporter::error(__CLASS__, __METHOD__ . ': failed to load structure', __FILE__, __LINE__);
		}
		
		if ($xincludeSupport)
		{
			$xpath = new \DOMXPath($doc);
			$xpath->registerNamespace('xinclude', 'http://www.w3.org/2001/XInclude');
			$nodes = $xpath->query('//xinclude:include');
			if ($nodes->length)
			{
				$doc->xinclude();
			}
		}
		
		$p = null;
		return self::createFromXmlNode($doc->documentElement, $p);
	}

	public static function createFromXmlNode(\DOMNode $node, StructureElement $parent = null, $postProcessElementCallback = null)
	{
		if (!($node && $node->namespaceURI == self::XMLNAMESPACE))
		{
			return ns\Reporter::error(__CLASS__, __METHOD__ . ': invalid namespace', __FILE__, __LINE__);
		}
		
		$datasource = (is_object($parent) && ($parent instanceof StructureElement)) ? $parent->root() : null;
		
		$o = null;
		$elementName = $node->hasAttribute('name') ? $node->getAttribute('name') : null;
		if (!$elementName)
		{
			$elementName = $node->hasAttributeNS(self::XMLNAMESPACE, 'name') ? $node->getAttributeNS(self::XMLNAMESPACE, 'name') : null;
		}
		
		if (!($elementName || ($node->localName == 'datasource')))
		{
			return ns\Reporter::error(__CLASS__, __METHOD__ . ': name attribute is missing');
		}
		
		switch ($node->localName)
		{
			case 'datasource':
				$o = new DatasourceStructure();
				break;
			case 'tableset':
			case 'database':
				$o = new TableSetStructure($parent, $elementName);
				break;
			case 'table':
				$o = new TableStructure($parent, $elementName);
				break;
			case 'column':
			case 'field':
				$o = new TableColumnStructure($parent, $elementName);
				break;
			default:
				return ns\Reporter::error(__CLASS__, __METHOD__ . ': invalid node ' . $node->localName);
		}
		
		if (is_null($parent))
		{
			if ($node->hasAttribute('version'))
			{
				$o->m_version = new ns\SemanticVersion($node->getAttribute('version'));
			}
		}
		
		$id = $node->hasAttribute('id') ? $node->getAttribute('id') : null;
		if (!$id)
		{
			$id = $node->hasAttributeNS(self::XMLNAMESPACE, 'id') ? $node->getAttributeNS(self::XMLNAMESPACE, 'id') : null;
		}
		
		$o->setIndex($id, $o);
		
		$o->constructFromXmlNode($node);
		
		if ($parent == null)
		{
			$o->postprocess();
		}
		
		if (is_callable($postProcessElementCallback))
		{
			call_user_func($postProcessElementCallback, $o);
		}
		
		return $o;
	}

	abstract protected function constructFromXmlNode(\DOMNode $node);

	/**
	 *
	 * @param string $a_name StructureElement
	 * @param StructureElement $a_parent
	 */
	protected function __construct($a_name, $a_parent = null)
	{
		$this->m_name = $a_name;
		$this->m_parent = $a_parent;
		
		$this->m_version = null;
		$this->m_children = new \ArrayObject(array ());
		$this->m_index = array ();
	}

	// Countable
	public function count()
	{
		return $this->m_children->count();
	}

	// IteratorAggregate
	public function getIterator()
	{
		return $this->m_children->getIterator();
	}

	// ArrayAccess
	public function offsetExists($a_key)
	{
		return $this->m_children->offsetExists($a_key);
	}

	public function offsetSet($a_iKey, $a_value)
	{
		ns\Reporter::error($this, __METHOD__ . '(): Read only access', __FILE__, __LINE__);
	}

	public function offsetUnset($key)
	{
		ns\Reporter::error($this, __METHOD__ . '(): Read only access', __FILE__, __LINE__);
	}

	public function offsetGet($key)
	{
		if ($this->m_children->offsetExists($key))
		{
			return $this->m_children->offsetGet($key);
		}
		
		$key = strtolower($key);
		if ($this->m_children->offsetExists($key))
		{
			return $this->m_children->offsetGet($key);
		}
		
		return null;
	}

	/**
	 *
	 * @return string
	 */
	public function elementKey()
	{
		return $this->m_name;
	}

	/**
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->m_name;
	}

	/**
	 *
	 * @return StructureElement
	 */
	public function parent()
	{
		return $this->m_parent;
	}

	/**
	 *
	 * @return array
	 */
	public function children()
	{
		return $this->m_children->getArrayCopy();
	}

	/**
	 *
	 * @param StructureElement $a_child
	 * @return StructureElement
	 */
	protected function appendChild(StructureElement $a_child)
	{
		$parent = $this->parent();
		$key = $a_child->elementKey();
		$this->m_children->offsetSet($key, $a_child);
		if (!($this->m_version instanceof StructureVersion))
		{
			$this->m_version = $a_child->m_version;
		}
		
		$this->m_index = array_merge($this->m_index, $a_child->m_index);
		$a_child->m_version = null;
		$a_child->m_index = null;
		
		return $a_child;
	}

	protected function clear()
	{
		$this->m_children->exchangeArray(array ());
	}

	/**
	 *
	 * @return StructureElement
	 */
	protected function root()
	{
		$res = $this;
		while ($res->parent())
		{
			$res = $res->parent();
		}
		
		return $res;
	}

	/**
	 *
	 * @return StructureVersion
	 */
	public function getStructureVersion()
	{
		if ($this->parent())
		{
			return $this->parent()->getStructureVersion();
		}
		
		return $this->m_version;
	}

	public function setIndex($name, $object)
	{
		if ($this->parent())
		{
			$this->parent()->setIndex($name, $object);
			return;
		}
		
		$this->m_index[$name] = $object;
	}

	public function getStructureElementIndex()
	{
		if ($this->parent())
		{
			return $this->parent()->getStructureElementIndex();
		}
		
		return $this->m_index;
	}

	/**
	 * Post process construction
	 */
	protected function postprocess()
	{
		foreach ($this->m_children as $n => $e)
		{
			$e->postprocess();
		}
	}

	/**
	 *
	 * @var string
	 */
	private $m_name;

	/**
	 *
	 * @var StructureElement
	 */
	private $m_parent;

	/**
	 *
	 * @var \ArrayObject
	 */
	private $m_children;

	/**
	 *
	 * @var StructureVersion
	 */
	private $m_version;

	/**
	 *
	 * @var array
	 */
	private $m_index;
}

/**
 * Table column properties
 */
class TableColumnStructure extends StructureElement
{

	public function __construct(/*TableStructure */$a_tableStructure, $a_name)
	{
		parent::__construct($a_name, $a_tableStructure);
		$this->m_columnProperties = array (
				self::ACCEPT_NULL => array (
						'set' => true,
						'value' => true 
				),
				self::AUTO_INCREMENT => array (
						'set' => true,
						'value' => false 
				),
				self::DECIMAL_COUNT => array (
						'set' => true,
						'value' => 0 
				),
				self::DATA_SIZE => array (
						'set' => false,
						'value' => 0 
				),
				self::PRIMARY_KEY => array (
						'set' => true,
						'value' => false 
				),
				self::INDEXED => array (
						'set' => true,
						'value' => false 
				),
				self::DATA_TYPE => array (
						'set' => true,
						'value' => kDataTypeString 
				),
				self::ENUMERATION => array (
						'set' => false,
						'value' => null 
				),
				kStructureValidatorClassname => array (
						'set' => false,
						'value' => null 
				),
				self::FOREIGN_KEY => array (
						'set' => false,
						'value' => null 
				),
				self::DEFAULT_VALUE => array (
						'set' => false,
						'value' => null 
				) 
		);
	}

	/**
	 *
	 * @return array
	 */
	public function getProperties()
	{
		return $this->m_columnProperties;
	}

	/**
	 *
	 * @param string $key
	 * @return boolean
	 */
	public function hasProperty($key)
	{
		return (\array_key_exists($key, $this->m_columnProperties) && $this->m_columnProperties[$key]['set']);
	}

	public function getProperty($key)
	{
		return $this->m_columnProperties[$key]['value'];
	}

	public function setProperty($key, $a_value)
	{
		if (\array_key_exists($key, $this->m_columnProperties))
		{
			$this->m_columnProperties[$key]['set'] = true;
			$this->m_columnProperties[$key]['value'] = $a_value;
		}
	}

	protected function constructFromXmlNode(\DOMNode $node)
	{
		$children = $node->getElementsByTagNameNS(self::XMLNAMESPACE, 'datatype');
		
		if (!($children && $children->length))
		{
			return;
		}
		
		$dataTypeNode = $children->item(0);
		$a = array (
				'binary' => kDataTypeBinary,
				'boolean' => kDataTypeBoolean,
				'numeric' => kDataTypeNumber,
				'timestamp' => kDataTypeTimestamp,
				'string' => kDataTypeString 
		);
		$typeNode = null;
		$type = null;
		
		foreach ($a as $k => $v)
		{
			$typeNode = $dataTypeNode->getElementsByTagNameNS(self::XMLNAMESPACE, $k);
			if ($typeNode && $typeNode->length)
			{
				$typeNode = $typeNode->item(0);
				$this->setProperty(self::DATA_TYPE, $v);
				$type = $v;
				break;
			}
		}
		
		if ($type & kDataTypeNumber)
		{
			$type = kDataTypeInteger;
			if ($typeNode->hasAttribute('autoincrement'))
			{
				$this->setProperty(self::AUTO_INCREMENT, true);
			}
			if ($typeNode->hasAttribute('length'))
			{
				$this->setProperty(self::DATA_SIZE, intval($typeNode->getAttribute('length')));
			}
			if ($typeNode->hasAttribute('decimals'))
			{
				$count = intval($typeNode->getAttribute('decimals'));
				$this->setProperty(self::DECIMAL_COUNT, $count);
				if ($count > 0)
				{
					$type = kDataTypeDecimal;
				}
			}
		}
		
		$children = $node->getElementsByTagNameNS(self::XMLNAMESPACE, 'default');
		if ($children && $children->length)
		{
			$defaultNode = $children->item(0);
			
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
				$children = $defaultNode->getElementsByTagNameNS(self::XMLNAMESPACE, $name);
				
				if (!($children && $children->length))
					continue;
				
				$value = $children->item(0)->nodeValue;
				
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
				
				$this->setProperty(self::DEFAULT_VALUE, $value);
				
				break;
			}
		}
	}

	protected function postprocess()
	{
		$fk = $this->getProperty(self::FOREIGN_KEY);
		if ($fk)
		{
			$tr = $fk['tableReference'];
			$table = null;
			if (array_key_exists('id', $tr))
			{
				$idx = $this->getStructureElementIndex();
				if (array_key_exists($tr['id'], $idx))
				{
					$table = $idx[$tr['id']];
				}
			}
			elseif (array_key_exists('name', $tr))
			{
				if ($p = $this->parent())
				{
					// parent table
					if ($p = $p->parent())
					{
						// parent db
						$table = $p->offsetGet($tr['name']);
					}
				}
			}
			
			if ($table)
			{
				$fk['column'] = $table->offsetGet($fk['columnName']);
				$fk['table'] = $table;
			}
			else
			{
				$fk = null;
				ns\Reporter::error($this, __METHOD__ . ': Failed to find table for foreign key on ' . $fk['columnName']);
			}
			
			$this->setProperty(self::FOREIGN_KEY, $fk);
		}
		
		parent::postprocess();
	}

	/**
	 *
	 * @var array
	 */
	private $m_columnProperties;
}

/**
 * Table properties
 *
 * @todo table constraints (primary keys etc. & index)
 */
class TableStructure extends StructureElement
{

	public function __construct(/*TableSetStructure */ $a_tablesetStructure, $a_name)
	{
		parent::__construct($a_name, $a_tablesetStructure);
	}

	public function getName()
	{
		return $this->root()->getTablePrefix() . parent::getName();
	}

	protected function constructFromXmlNode(\DOMNode $node)
	{
		$xpath = new \DOMXPath($node->ownerDocument);
		$xpath->registerNamespace('sql', self::XMLNAMESPACE);
		
		$primaryKeyColumnNodes = $xpath->query('sql:primarykey/sql:column', $node);
		$columnNodes = $xpath->query('sql:column|sql:field', $node);
		foreach ($columnNodes as $columnNode)
		{
			$fs = self::createFromXmlNode($columnNode, $this);
			
			foreach ($primaryKeyColumnNodes as $primaryKeyColumnNode)
			{
				if ($primaryKeyColumnNode->getAttribute('name') == $fs->getName())
				{
					$fs->setProperty(self::PRIMARY_KEY, true);
				}
			}
			
			$this->appendChild($fs);
		}
		
		$foreignKeyNodes = $xpath->query('sql:foreignkey', $node);
		foreach ($foreignKeyNodes as $foreignKey)
		{
			$columnNode = $foreignKey->getElementsByTagNameNS(self::XMLNAMESPACE, 'column')->item(0);
			$columnName = $columnNode->getAttribute('name');
			$column = $this->offsetGet($columnName);
			
			$referenceNode = $foreignKey->getElementsByTagNameNS(self::XMLNAMESPACE, 'reference')->item(0);
			$referenceColumnNode = $referenceNode->getElementsByTagNameNS(self::XMLNAMESPACE, 'column')->item(0);
			$referenceTableNode = $referenceNode->getElementsByTagNameNS(self::XMLNAMESPACE, 'tableref')->item(0);
			
			$property = array (
					'columnName' => $referenceColumnNode->getAttribute('name'),
					'tableReference' => ($referenceTableNode->hasAttribute('id') ? array (
							'id' => $referenceTableNode->getAttribute('id') 
					) : array (
							'name' => $referenceTableNode->getAttribute('name') 
					)) 
			);
			
			$column->setProperty(self::FOREIGN_KEY, $property);
		}
	}

	public function getPrimaryKeyColumns()
	{
		$result = array ();
		foreach ($this as $n => $c)
		{
			if ($c->getProperty(self::PRIMARY_KEY))
			{
				$result[$n] = $c;
			}
		}
		
		return $result;
	}

	public function getForeignKeyReferences()
	{
		$result = array ();
		foreach ($this as $n => $c)
		{
			$fk = $c->getProperty(self::FOREIGN_KEY);
			if ($fk)
			{
				$result[$n] = $fk;
			}
		}
		
		return $result;
	}

	public function addColumnStructure(TableColumnStructure $f)
	{
		$this->appendChild($f);
	}
}

/**
 * Table set structure definition
 */
class TableSetStructure extends StructureElement
{

	public function __construct(/*DatasourceStructure */$a_datasourceStructure, $a_name)
	{
		parent::__construct($a_name, $a_datasourceStructure);
	}

	public final function addTableStructure(TableStructure $a_table)
	{
		$this->appendChild($a_table);
	}

	protected function constructFromXmlNode(\DOMNode $node)
	{
		$xpath = new \DOMXPath($node->ownerDocument);
		$xpath->registerNamespace('sql', self::XMLNAMESPACE);
		
		$tnodes = $xpath->query('sql:table', $node);
		foreach ($tnodes as $tnode)
		{
			$this->appendChild(self::createFromXmlNode($tnode, $this));
		}
	}
}

/**
 * Data source structure definition
 */
class DatasourceStructure extends StructureElement
{

	/**
	 *
	 * @param string $a_name Datasource class name
	 * @param number $flags
	 */
	public function __construct($a_name = 'Datasource', $flags = 0)
	{
		parent::__construct($a_name);
		$this->m_flags = $flags;
	}

	/**
	 *
	 * @return string
	 */
	public function getTablePrefix()
	{
		return $this->m_tablePrefix;
	}

	/**
	 *
	 * @param string $prefix
	 */
	public function setTablePrefix($prefix)
	{
		$this->m_tablePrefix = $prefix;
	}

	protected function constructFromXmlNode(\DOMNode $node)
	{
		$xpath = new \DOMXPath($node->ownerDocument);
		$xpath->registerNamespace('sql', self::XMLNAMESPACE);
		
		$dbnodes = $xpath->query('sql:database|sql:tableset', $node);
		foreach ($dbnodes as $dbnode)
		{
			$db = self::createFromXmlNode($dbnode, $this);
			if ($db)
			{
				$this->appendChild($db);
			}
			else
			{
				return ns\Reporter::error($this, __METHOD__ . ': Failed to create sub tableset structure');
			}
		}
	}

	/**
	 *
	 * @var integer
	 */
	protected $m_flags;

	/**
	 *
	 * @var string
	 */
	private $m_tablePrefix;
}
