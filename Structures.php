<?php

/**
 * Copyright © 2012-2018 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 * @package SQL
 */
namespace NoreSources\SQL;

use NoreSources as ns;
use NoreSources\SQL\Constants as K;

class StructureException extends \Exception
{

	public function __construct($message)
	{
		parent::__construct($message);
	}
}

abstract class StructureElement implements \ArrayAccess, \IteratorAggregate, \Countable
{
	const DATA_TYPE = K::PROPERTY_COLUMN_DATA_TYPE;
	const PRIMARY_KEY = K::PROPERTY_COLUMN_PRIMARYKEY;
	const AUTO_INCREMENT = K::PROPERTY_COLUMN_AUTOINCREMENT;
	const FOREIGN_KEY = K::PROPERTY_COLUMN_FOREIGNKEY;
	const INDEXED = K::PROPERTY_COLUMN_INDEXED;
	const ACCEPT_NULL = K::PROPERTY_COLUMN_NULL;
	const DATA_SIZE = K::PROPERTY_COLUMN_DATA_SIZE;
	const DECIMAL_COUNT = K::PROPERTY_COLUMN_DECIMAL_COUNT;
	const ENUMERATION = K::PROPERTY_COLUMN_ENUMERATION;
	const DEFAULT_VALUE = K::PROPERTY_COLUMN_DEFAULT_VALUE;

	/*
	 * @var string ns-xml SQL schema namespace
	 */
	const XMLNAMESPACE = 'http://xsd.nore.fr/sql';

	/**
	 * @param string|\DOMNode $input
	 * @param StructureElement $parent
	 * @param unknown $postProcessElementCallback
	 * @return \NoreSources\SQL\StructureElement
	 */
	public static function create($input, StructureElement $parent = null, $postProcessElementCallback = null)
	{
		if (is_string($input))
		{
			if (is_file($input))
			{
				return self::createFromXmlFile($input, $postProcessElementCallback);
			}
			throw new \BadMethodCallException('Invalid structure file ' . $input);
		}
		else if ($input instanceof \DOMNode)
		{
			return self::createFromXmlNode($input, $parent, $postProcessElementCallback);
		}

		throw new \BadMethodCallException(__METHOD__);
	}

	/**
	 * @param unknown $filename XML SQL structure to load
	 * @param unknown $postProcessElementCallback A delegate called for each node. The currently processed node
	 * @param string $xincludeSupport Resolve XInclude instructions
	 */
	public static function createFromXmlFile($filename, $postProcessElementCallback = null, $xincludeSupport = true)
	{
		$doc = new \DOMDocument();
		if (!$doc->load($filename))
		{
			throw new StructureException('Failed to load structure file ' . $filename);
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

	/**
	 * @param \DOMNode $node
	 * @param StructureElement $parent
	 * @param unknown $postProcessElementCallback
	 * @return \NoreSources\SQL\StructureElement
	 */
	public static function createFromXmlNode(\DOMNode $node, StructureElement $parent = null, $postProcessElementCallback = null)
	{
		if (!($node && $node->namespaceURI == self::XMLNAMESPACE))
		{
			throw new StructureException('Invalid XML namespace ' . $node->namespaceURI);
		}

		$datasource = (($parent instanceof StructureElement)) ? $parent->root() : null;

		$o = null;
		$elementName = $node->hasAttribute('name') ? $node->getAttribute('name') : null;
		if (!$elementName)
		{
			$elementName = $node->hasAttributeNS(self::XMLNAMESPACE, 'name') ? $node->getAttributeNS(self::XMLNAMESPACE, 'name') : null;
		}

		if (!($elementName || ($node->localName == 'datasource')))
		{
			throw new StructureException('Missing name attribute');
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
				throw new StructureException('Invalid node ' . $node->localName);
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
		throw new \BadMethodCallException('Read only access');
	}

	public function offsetUnset($key)
	{
		throw new \BadMethodCallException('Read only access');
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
	 * @param unknown $tree
	 * @return \NoreSources\SQL\StructureElement
	 */
	public function findDescendant($tree)
	{
		if (is_string($tree))
			return $this->offsetGet($tree);

		$e = $this;
		foreach ($tree as $key)
		{
			$e = $e->offsetGet($key);
			if (!$e)
				break;
		}

		return $e;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->m_name;
	}

	/**
	 * @param StatementBuilder $builder
	 * @return string
	 */
	public function getPath(StatementBuilder $builder = null)
	{
		$s = ($builder instanceof StatementBuilder) ? $builder->escapeIdentifier($this->getName()) : $this->getName();
		$p = $this->parent();
		while ($p && !($p instanceof DatasourceStructure))
		{
			$s = (($builder instanceof StatementBuilder) ? $builder->escapeIdentifier($p->getName()) : $p->getName()) . '.' . $s;
			$p = $p->parent();
		}

		return $s;
	}

	/**
	 * Get ancestor
	 * @param number $depth
	 * @return \NoreSources\SQL\StructureElement
	 */
	public function parent($depth = 1)
	{
		$p = $this->m_parent;
		while ($p && ($depth > 1))
		{
			$p = $p->m_parent;
			$depth--;
		}

		return $p;
	}

	/**
	 * @return array
	 */
	public function children()
	{
		return $this->m_children->getArrayCopy();
	}

	/**
	 * @param StructureElement $a_child
	 * @return StructureElement
	 */
	protected function appendChild(StructureElement $a_child)
	{
		$parent = $this->parent();
		$key = $a_child->getName();
		$this->m_children->offsetSet($key, $a_child);
		if (!($this->m_version instanceof ns\SemanticVersion))
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
	 * @return \NoreSources\SemanticVersion
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
	 * @var string
	 */
	private $m_name;

	/**
	 * @var StructureElement
	 */
	private $m_parent;

	/**
	 * @var \ArrayObject
	 */
	private $m_children;

	/**
	 * @var \NoreSources\SemanticVersion
	 */
	private $m_version;

	/**
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
						'value' => K::kDataTypeString
				),
				self::ENUMERATION => array (
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
	 * @return array
	 */
	public function getProperties()
	{
		return $this->m_columnProperties;
	}

	/**
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
				'binary' => K::kDataTypeBinary,
				'boolean' => K::kDataTypeBoolean,
				'numeric' => K::kDataTypeNumber,
				'timestamp' => K::kDataTypeTimestamp,
				'string' => K::kDataTypeString
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

		if ($type & K::kDataTypeNumber)
		{
			$type = K::kDataTypeInteger;
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
					$type = K::kDataTypeDecimal;
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
				throw new StructureException('Failed to find table for foreign key on ' . $fk['columnName']);
			}

			$this->setProperty(self::FOREIGN_KEY, $fk);
		}

		parent::postprocess();
	}

	/**
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
	 * @param string $a_name Datasource class name
	 * @param number $flags
	 */
	public function __construct($a_name = 'Datasource', $flags = 0)
	{
		parent::__construct($a_name);
		$this->m_flags = $flags;
	}

	/**
	 * @return string
	 */
	public function getTablePrefix()
	{
		return $this->m_tablePrefix;
	}

	/**
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
				throw new StructureException(': Failed to create sub tableset structure');
			}
		}
	}

	/**
	 * @var integer
	 */
	protected $m_flags;

	/**
	 * @var string
	 */
	private $m_tablePrefix;
}

class StructureResolverException extends \Exception
{

	public function __construct($path)
	{
		parent::__construct($path . ' not found');
	}
}

class StructureResolver
{

	/**
	 * @param StructureElement $pivot Reference element
	 */
	public function __construct(StructureElement $pivot = null)
	{
		$this->cache = new \ArrayObject(array (
				'aliases' => new \ArrayObject(),
				'columns' => new \ArrayObject(),
				'tables' => new \ArrayObject(),
				'tablesets' => new \ArrayObject(),
				'datasource' => new \ArrayObject()
		));

		$this->structureAliases = new \ArrayObject();

		if ($pivot instanceof StructureElement)
		{
			$this->setPivot($pivot);
		}
	}

	/**
	 * Define the reference node and reset cache
	 * @param StructureElement $pivot
	 */
	public function setPivot(StructureElement $pivot)
	{
		foreach ($this->cache as $key => &$table)
		{
			$table->exchangeArray(array ());
		}

		$this->pivot = $pivot;
		$key = self::getKey($pivot);
		$this->cache[$key]->offsetSet($pivot->getName(), $pivot);
		$this->cache[$key]->offsetSet($pivot->getPath(), $pivot);
		$p = $pivot->parent();
		while ($p instanceof StructureElement)
		{
			$this->cache[self::getKey($p)]->offsetSet($p->getName(), $p);
			$p = $p->parent();
		}
	}

	/**
	 * @param string $path
	 * @throws StructureResolverException
	 * @return \NoreSources\SQL\TableColumnStructure
	 */
	public function findColumn($path)
	{
		if ($this->cache['columns']->offsetExists($path))
		{
			return $this->cache['columns'][$path];
		}

		$x = explode('.', $path);
		$c = count($x);
		$name = $x[$c - 1];

		$table = null;

		if ($c == 1)
		{
			$table = $this->getDefaultTable();
		}
		elseif ($c == 2)
		{
			$table = $this->findTable($x[0]);
		}
		elseif ($c == 3)
		{
			$tableset = $this->findTableset($x[0]);
			if ($tableset)
			{
				$table = $tableset->offsetGet($x[1]);
			}
		}

		if (!($table instanceof TableStructure))
			return null;

		$column = $table->offsetGet($name);

		if ($column instanceof TableColumnStructure)
		{
			$this->cache['columns']->offsetSet($path, $column);
		}
		else
		{
			throw new StructureResolverException($path);
		}

		return $column;
	}

	/**
	 * @param string $path
	 * @throws StructureResolverException
	 * @return \NoreSources\SQL\TableStructure
	 */
	public function findTable($path)
	{
		if ($this->cache['tables']->offsetExists($path))
		{
			return $this->cache['tables'][$path];
		}

		$x = explode('.', $path);
		$c = count($x);
		$name = $x[$c - 1];

		$tableset = null;

		if ($c == 1)
		{
			$tableset = $this->getDefaultTableset();
		}
		else if ($c == 2)
		{
			$tableset = $this->findTableset($x[0]);
		}

		$table = ($tableset instanceof TableSetStructure) ? $tableset->offsetGet($name) : null;

		if ($table instanceof TableStructure)
		{
			$this->cache['tables']->offsetSet($path, $table);
		}
		else
		{
			throw new StructureResolverException($path);
		}

		return $table;
	}

	/**
	 * @param string $path
	 * @throws StructureResolverException
	 * @return \NoreSources\SQL\TableSetStructure
	 */
	public function findTableset($path)
	{
		if ($this->cache['tablesets']->offsetExists($path))
		{
			return $this->cache['tablesets'][$path];
		}

		$datasource = $this->pivot;
		while ($datasource && !($datasource instanceof DatasourceStructure))
		{
			$datasource = $datasource->parent();
		}

		$tableset = ($datasource instanceof DatasourceStructure) ? $datasource->offsetGet($path) : null;

		if ($tableset instanceof TableSetStructure)
		{
			$this->cache['tablesets']->offsetSet($path, $tableset);
		}
		else
		{
			throw new StructureResolverException($path);
		}
		return $tableset;
	}

	/**
	 * @param string $alias
	 * @param StructureElement $structure
	 */
	public function setAlias($alias, $reference)
	{
		$this->cache[self::getKey($reference)]->offsetSet($alias, $reference);
		$this->structureAliases->offsetSet($alias, $reference);
	}

	public function isAlias($identifier)
	{
		return $this->structureAliases->offsetExists($identifier);
	}

	private static function getKey($item)
	{
		if ($item instanceof TableColumnStructure)
		{
			return 'columns';
		}
		elseif ($item instanceof TableStructure)
		{
			return 'tables';
		}
		elseif ($item instanceof TableSetStructure)
		{
			return 'tablesets';
		}
		elseif ($item instanceof DatasourceStructure)
		{
			return 'datasource';
		}
	}

	private function getDefaultTableset()
	{
		if ($this->pivot instanceof DatasourceStructure)
		{
			if ($this->pivot->count() == 1)
			{
				return $this->pivot->getIterator()->current();				
			}
		}
		elseif ($this->pivot instanceof TableSetStructure)
			return $this->pivot;
		elseif ($this->pivot instanceof TableStructure)
			return $this->pivot->parent();
		elseif ($this->pivot instanceof TableColumnStructure)
			return $this->pivot->parent(2);

		return null;
	}

	private function getDefaultTable()
	{
		if ($this->pivot instanceof TableColumnStructure)
		{
			return $this->pivot->parent();
		}
		elseif ($this->pivot instanceof TableStructure)
		{
			return $this->pivot;
		}

		return null;
	}

	public function debugCache()
	{
		$a = array ();
		foreach ($this->cache as $type => $structures)
		{
			$a[$type] = array ();
			foreach ($structures as $name => $structure)
			{
				$a[$type][] = $name;
			}
		}

		return $a;
	}

	/**
	 * @var StructureElement
	 */
	private $pivot;

	/**
	 * @var \ArrayObject
	 */
	private $cache;

	/**
	 * @var \ArrayObject
	 */
	private $structureAliases;
}
