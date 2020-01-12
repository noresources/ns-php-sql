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

use NoreSources\SQL\Statement\StatementBuilder;
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
	 * @param \NoreSources\SQL\Statement\Builder $builder
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
