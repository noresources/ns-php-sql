<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement;

use NoreSources\Container;
use NoreSources\Stack;
use NoreSources\SQL\DBMS\PlatformInterface;
use NoreSources\SQL\Structure\ColumnDescriptionMapInterface;
use NoreSources\SQL\Structure\StructureElementInterface;
use NoreSources\SQL\Structure\StructureResolver;
use NoreSources\SQL\Structure\StructureResolverInterface;
use NoreSources\SQL\Structure\VirtualStructureResolver;
use NoreSources\SQL\Structure\Traits\StructureResolverProviderTrait;
use NoreSources\SQL\Syntax\TokenStreamContextInterface;
use NoreSources\SQL\Syntax\Statement\Traits\InputDataTrait;
use NoreSources\SQL\Syntax\Statement\Traits\OutputDataTrait;

/**
 * Token stream context dedicated to DBMS statement building
 */
class StatementTokenStreamContext implements
	TokenStreamContextInterface
{
	use InputDataTrait;
	use OutputDataTrait;
	use StructureResolverProviderTrait;

	/**
	 * Constructor and __get magic method key.
	 */
	const PLATFORM = 'platform';

	/**
	 * Constructor and __get magic method key.
	 */
	const PIVOT = 'pivot';

	/**
	 * Constructor and __get magic method key.
	 */
	const RESOLVER = 'resolver';

	/**
	 *
	 * @param array|... ...$arguments
	 *        	A PlatformInterface, a StructureResolverInterface
	 *        	and
	 *        	an optional StructureElementInterface in any order or placed in a key-value pair
	 *        	array
	 * @throws \BadMethodCallException If no structure resolver nor structure element is provided, a
	 *         virtual structure resolver is created by default. The virtual resolver will be
	 *         transformed to a normal resolver if a pivot from another structure is provided later.
	 */
	public function __construct(...$arguments)
	{
		if (\count($arguments) == 1 &&
			Container::keyExists($arguments, 0) &&
			\is_array($arguments[0]))
			$arguments = $arguments[0];

		$keys = [
			self::PLATFORM => 'setPlatform',
			self::RESOLVER => 'setStructureResolver'
		];

		foreach ($arguments as $key => $value)
		{

			if (\is_string($key))
			{
				if ($m = Container::keyValue($keys, $key))
					\call_user_func([
						$this,
						$m
					], $value);
			}
			elseif ($value instanceof PlatformInterface)
				$this->platform = $value;
			elseif ($value instanceof StructureResolverInterface)
				$this->setStructureResolver($value);
		}

		if (!isset($this->platform))
			throw new \BadMethodCallException(
				self::PLATFORM . ' must be provided');

		if (!isset($this->structureResolver))
			$this->setStructureResolver(new VirtualStructureResolver());

		foreach ($arguments as $key => $value)
		{
			if ((\strval($key) == self::PIVOT) ||
				($value instanceof StructureElementInterface))
			{
				$this->setPivot($value);
			}
		}

		if ($this->getPivot() === null &&
			$this->structureResolver instanceof VirtualStructureResolver)
			$this->setPivot($this->structureResolver->getStructure());

		$this->resetState($this->getPivot());
	}

	/**
	 * Clone the current context and reset its state data.
	 */
	public function __clone()
	{
		$this->resetState($this->getPivot());
	}

	public function __get($member)
	{
		switch ($member)
		{
			case self::PLATFORM:
				return $this->getPlatform();
			case self::RESOLVER:
				return $this->getStructureResolver();
			case self::PIVOT:
				return $this->getPivot();
		}

		throw new \LogicException($member . ' is not a valid member');
	}

	public function setPivot(StructureElementInterface $pivot)
	{
		if ($this->structureResolver instanceof VirtualStructureResolver)
		{
			$root = $this->structureResolver->getStructure();
			$p = $pivot;
			while ($p->getParentElement())
				$p = $p->getParentElement();

			if ($root != $p)
				$this->setStructureResolver(new StructureResolver());
		}

		return $this->structureResolver->setPivot($pivot);
	}

	public function getPlatform()
	{
		return $this->platform;
	}

	public function setResultColumn($index, $data, $as = null)
	{
		if ($this->statementElements->count() > 1)
		{
			$this->statementElements->getResultColumns()->setColumn(
				$index, $data, $as);
			return;
		}

		$this->resultColumns->setColumn($index, $data, $as);
	}

	public function setStatementType($type)
	{
		if ($this->statementElements->count() > 1)
			return;

		$this->statementType = $type;
	}

	public function findColumn($path)
	{
		if ($this->statementElements->isEmpty())
			$this->statementElements->push(new StatementElementDMap());

		if ($this->statementElements->aliases->offsetExists($path))
			return $this->statementElements->aliases->offsetGet($path);

		return $this->structureResolver->findColumn($path);
	}

	public function setAlias($alias, $reference)
	{
		if ($reference instanceof StructureElementInterface)
			return $this->structureResolver->setAlias($alias, $reference);
		else
		{
			if ($this->statementElements->isEmpty())
				$this->statementElements->push(
					new StatementElementDMap());

			$this->statementElements->aliases->offsetSet($alias,
				$reference);
		}
	}

	public function isAlias($identifier)
	{
		if ($this->statementElements->count())
		{
			if ($this->statementElements->aliases->offsetExists(
				$identifier))
				return true;
		}

		return $this->structureResolver->isAlias($identifier);
	}

	public function setTemporaryTable($name,
		ColumnDescriptionMapInterface $columns)
	{
		$this->structureResolver->setTemporaryTable($name, $columns);
	}

	public function pushResolverContext(
		StructureElementInterface $pivot)
	{
		$this->statementElements->push(new StatementElementDMap());
		$this->structureResolver->pushResolverContext($pivot);
	}

	public function popResolverContext()
	{
		/**
		 *
		 * @todo get result columns and data row container reference from current context
		 *       and put them in the list of "available columns and table"
		 */
		$this->statementElements->pop();
		$this->structureResolver->popResolverContext();
	}

	protected function setPlatform(PlatformInterface $platform)
	{
		$this->platform = $platform;
	}

	protected function resetState(
		StructureElementInterface $pivot = null)
	{
		$this->initializeInputData(null);
		$this->initializeOutputData(null);
		$this->statementElements = new Stack();
		if ($pivot instanceof StructureElementInterface)
			$this->setPivot($pivot);
	}

	/**
	 *
	 * @var \Noresources\Stack Stack of \ArrayObject
	 */
	private $statementElements;

	/**
	 *
	 * @var PlatformInterface
	 */
	private $platform;
}

/**
 * Private class
 */
class StatementElementDMap implements StatementOutputDataInterface
{

	use OutputDataTrait;

	/**
	 *
	 * @var \ArrayObject
	 */
	public $aliases;

	/**
	 *
	 * @var \ArrayObject
	 */
	public $temporaryTables;

	public function __construct()
	{
		$this->initializeOutputData();
		$this->aliases = new \ArrayObject();
		$this->temporaryTables = new \ArrayObject();
	}
}