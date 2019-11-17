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

use NoreSources\SQL\Constants as K;
use NoreSources as ns;

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
	 *
	 * @var StructureElement
	 */
	private $structure;
}

abstract class StructureElement implements \ArrayAccess, \IteratorAggregate, \Countable
{

	/**
	 *
	 * @param string $name
	 *        	StructureElement
	 * @param StructureElement $parent
	 */
	protected function __construct($name, $parent = null)
	{
		if (!(is_string($name) && strlen($name)))
			throw new StructureException(
				'Invalid element name (' . ns\TypeDescription::getName($name) . ')');
		$this->elementName = $name;
		$this->parentElement = $parent;

		$this->subElements = new \ArrayObject([]);
	}

	public function __clone()
	{
		foreach ($this->subElements as $key => $value)
		{
			$e = clone $value;
			$e->setParent($this);
			$this->subElements->offsetSet($key, $e);
		}
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
	 *
	 * @param StructureElement $tree
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
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->elementName;
	}

	/**
	 *
	 * @param StatementBuilder $builder
	 * @return string
	 */
	public function getPath(StatementBuilder $builder = null)
	{
		$s = ($builder instanceof StatementBuilder) ? $builder->escapeIdentifier($this->getName()) : $this->getName();
		$p = $this->parent();
		while ($p && !($p instanceof DatasourceStructure))
		{
			$s = (($builder instanceof StatementBuilder) ? $builder->escapeIdentifier($p->getName()) : $p->getName()) .
				'.' . $s;
			$p = $p->parent();
		}

		return $s;
	}

	/**
	 * Get ancestor
	 *
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
	 *
	 * @return array
	 */
	public function children()
	{
		return $this->subElements->getArrayCopy();
	}

	/**
	 *
	 * @param StructureElement $a_child
	 * @return StructureElement
	 */
	public function appendChild(StructureElement $a_child)
	{
		$key = $a_child->getName();
		$a_child->setParent($this);
		$this->subElements->offsetSet($key, $a_child);

		return $a_child;
	}

	/**
	 * Detach element from its parent
	 */
	public function detach()
	{
		$this->parentElement = null;
	}

	protected function clear()
	{
		$this->subElements->exchangeArray([]);
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
	 * Post process construction
	 */
	protected function postprocess()
	{
		foreach ($this->subElements as $n => $e)
		{
			$e->postprocess();
		}
	}

	public function setParent(StructureElement $parent = null)
	{
		$this->parentElement = $parent;
	}

	/**
	 *
	 * @var string
	 */
	private $elementName;

	/**
	 *
	 * @var StructureElement
	 */
	private $parentElement;

	/**
	 *
	 * @var \ArrayObject
	 */
	private $subElements;
}

/**
 * Table column properties
 */
class TableColumnStructure extends StructureElement implements ColumnPropertyMap
{

	const DATATYPE = K::COLUMN_PROPERTY_DATA_TYPE;

	const AUTO_INCREMENT = K::COLUMN_PROPERTY_AUTO_INCREMENT;

	const ACCEPT_NULL = K::COLUMN_PROPERTY_ACCEPT_NULL;

	const DATA_SIZE = K::COLUMN_PROPERTY_DATA_SIZE;

	const FRACTION_DIGIT_COUNT = K::COLUMN_PROPERTY_FRACTION_DIGIT_COUNT;

	const ENUMERATION = K::COLUMN_PROPERTY_ENUMERATION;

	const DEFAULT_VALUE = K::COLUMN_PROPERTY_DEFAULT_VALUE;

	use ColumnPropertyMapTrait;

	public function __construct(/*TableStructure */$a_tableStructure, $name)
	{
		parent::__construct($name, $a_tableStructure);
		$this->initializeColumnProperties();
	}

	/**
	 * Clone default value if any.
	 */
	public function __clone()
	{
		parent::__clone();
		if ($this->hasColumnProperty(self::DEFAULT_VALUE))
		{
			$this->setColumnProperty(self::DEFAULT_VALUE,
				clone $this->getColumnProperty(self::DEFAULT_VALUE));
		}
	}
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
	 *
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
	 *
	 * @param TableConstraint $constraint
	 *        	Constraint to add. If The constraint is the primary key constraint, it will replace
	 *        	the existing one.
	 * @throws StructureException
	 */
	public function addConstraint(TableConstraint $constraint)
	{
		if ($constraint instanceof PrimaryKeyTableConstraint)
		{
			foreach ($this->constraints as $value)
			{
				if ($value instanceof PrimaryKeyTableConstraint)
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
			if ($c === $constraint)
				$this->constraints->offsetUnset($i);
		}
	}

	/**
	 *
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
	 *
	 * @param string $name
	 *        	Datasource class name
	 */
	public function __construct($name = 'datasource')
	{
		parent::__construct(((is_string($name) && strlen($name)) ? $name : 'datasource'));
	}
}

