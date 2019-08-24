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

	public function __construct($message, StructureElement $element = null)
	{
		parent::__construct($message);
		$this->structure = $element;
	}

	public function getStructureElement()
	{
		return $this->structure();
	}

	/**
	 * @var StructureElement
	 */
	private $structure;
}

abstract class StructureElement implements \ArrayAccess, \IteratorAggregate, \Countable
{

	/**
	 * @param string $name StructureElement
	 * @param StructureElement $parent
	 */
	protected function __construct($name, $parent = null)
	{
		if (!(is_string($name) && strlen($name)))
			throw new StructureException('Invalid element name (' . ns\TypeDescription::getName($name) . ')');
		$this->elementName = $name;
		$this->parentElement = $parent;

		$this->subElements = new \ArrayObject(array ());
	}

	// Countable
	public function count()
	{
		return $this->subElements->count();
	}

	// IteratorAggregate
	public function getIterator()
	{
		return $this->subElements->getIterator();
	}

	// ArrayAccess
	public function offsetExists($a_key)
	{
		return $this->subElements->offsetExists($a_key);
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
		if ($this->subElements->offsetExists($key))
		{
			return $this->subElements->offsetGet($key);
		}

		$key = strtolower($key);
		if ($this->subElements->offsetExists($key))
		{
			return $this->subElements->offsetGet($key);
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
		return $this->elementName;
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
		$p = $this->parentElement;
		while ($p && ($depth > 1))
		{
			$p = $p->parentElement;
			$depth--;
		}

		return $p;
	}

	/**
	 * @return array
	 */
	public function children()
	{
		return $this->subElements->getArrayCopy();
	}

	/**
	 * @param StructureElement $a_child
	 * @return StructureElement
	 */
	public function appendChild(StructureElement $a_child)
	{
		$parent = $this->parent();
		$key = $a_child->getName();
		$this->subElements->offsetSet($key, $a_child);

		return $a_child;
	}

	protected function clear()
	{
		$this->subElements->exchangeArray(array ());
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
	 * Post process construction
	 */
	protected function postprocess()
	{
		foreach ($this->subElements as $n => $e)
		{
			$e->postprocess();
		}
	}

	/**
	 * @var string
	 */
	private $elementName;

	/**
	 * @var StructureElement
	 */
	private $parentElement;

	/**
	 * @var \ArrayObject
	 */
	private $subElements;
}

/**
 * Table column properties
 */
class TableColumnStructure extends StructureElement
{
	const DATA_TYPE = K::PROPERTY_COLUMN_DATA_TYPE;
	const AUTO_INCREMENT = K::PROPERTY_COLUMN_AUTOINCREMENT;
	const ACCEPT_NULL = K::PROPERTY_COLUMN_NULL;
	const DATA_SIZE = K::PROPERTY_COLUMN_DATA_SIZE;
	const DECIMAL_COUNT = K::PROPERTY_COLUMN_DECIMAL_COUNT;
	const ENUMERATION = K::PROPERTY_COLUMN_ENUMERATION;
	const DEFAULT_VALUE = K::PROPERTY_COLUMN_DEFAULT_VALUE;

	public function __construct(/*TableStructure */$a_tableStructure, $name)
	{
		parent::__construct($name, $a_tableStructure);
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
				self::DATA_TYPE => array (
						'set' => true,
						'value' => K::DATATYPE_STRING
				),
				self::ENUMERATION => array (
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
	 * Get column properties
	 * @return array
	 */
	public function getProperties()
	{
		$a = array ();
		foreach ($this->m_columnProperties as $key => $property)
		{
			if ($property['set'])
				$a[$key] = $property['value'];
		}

		return $a;
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

	/**
	 * 
	 * @param TableSetStructure $a_tablesetStructure
	 * @param string $name
	 */
	public function __construct(/*TableSetStructure */ $a_tablesetStructure, $name)
	{
		parent::__construct($name, $a_tablesetStructure);

		$this->constraints = new \ArrayObject();
	}

	/**
	 * @property-read \ArrayObject $constraints Table constraints
	 * @param string $member
	 * @throws \InvalidArgumentException
	 * @return ArrayObject
	 */
	public function __get($member)
	{
		if ($member == 'constraints')
		{
			return $this->constraints;
		}

		throw new \InvalidArgumentException($member);
	}

	/**
	 * Add table constraint
	 * @param TableConstraint $constraint Constraint to add. If The constraint is the primary key constraint, it will replace
	 *        the existing one.
	 * @throws StructureException
	 */
	public function addConstraint(TableConstraint $constraint)
	{
		if ($constraint instanceof KeyTableConstraint && $constraint->type == K::TABLE_CONSTRAINT_PRIMARY_KEY)
		{
			foreach ($this->constraints as $value)
			{
				if ($value instanceof KeyTableConstraint && $value->type == K::TABLE_CONSTRAINT_PRIMARY_KEY)
				{
					throw new StructureException($this, 'Primary key already exists.');
				}
			}
		}
		
		$this->constraints->append($constraint);
	}
	
	public function removeConstraint($constraint)
	{
		foreach ($this->constraints as $i => $c)
		{
			if ($c === $constraint) $this->constraints->offsetUnset($i);
		}
	}

	/**
	 * @var \ArrayObject
	 */
	private $constraints;
}

/**
 * Table set structure definition
 */
class TableSetStructure extends StructureElement
{

	public function __construct(/*DatasourceStructure */$a_datasourceStructure, $name)
	{
		parent::__construct($name, $a_datasourceStructure);
	}

	public final function addTableStructure(TableStructure $a_table)
	{
		$this->appendChild($a_table);
	}
}

/**
 * Data source structure definition
 */
class DatasourceStructure extends StructureElement
{

	/**
	 * @param string $name Datasource class name
	 */
	public function __construct($name = 'datasource')
	{
		parent::__construct(((is_string($name) && strlen($name)) ? $name : 'datasource'));
	}
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
	 * @return \NoreSources\SQL\StructureElement
	 */
	public function getPivot()
	{
		return $this->pivot;
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

		throw new StructureResolverException('Default tableset');
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
		else
		{
			$tableset = $this->getDefaultTableset();
			if ($tableset instanceof TableSetStructure && ($tableset->count() == 1))
				return $tableset->getIterator()->current();
		}

		throw new StructureResolverException('Default table');
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
