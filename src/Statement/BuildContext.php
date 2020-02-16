<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\Statement;

use NoreSources\Stack;
use NoreSources\StringRepresentation;
use NoreSources\SQL\Structure\StructureElement;
use NoreSources\SQL\Structure\StructureResolver;
use NoreSources\SQL\Structure\StructureResolverAwareInterface;
use NoreSources\SQL\Structure\StructureResolverAwareTrait;
use NoreSources\SQL\Structure\StructureResolverInterface;

/**
 * Statement building context data
 */
class BuildContext implements InputData, OutputData, StringRepresentation,
	StructureResolverInterface, StructureResolverAwareInterface, StatementBuilderAwareInterface
{
	use InputDataTrait;
	use OutputDataTrait;
	use StructureResolverAwareTrait;

	/**
	 * SQL statement string
	 *
	 * @var string
	 */
	public $sql;

	/**
	 *
	 * @param StatementBuilder $builder
	 * @param StructureElement $pivot
	 */
	public function __construct(StatementBuilder $builder, StructureElement $pivot = null)
	{
		$this->initializeInputData(null);
		$this->initializeOutputData(null);
		$this->sql = '';
		$this->builder = $builder;
		$this->setStructureResolver(new StructureResolver($pivot));
		$this->resultColumnAliases = new Stack();
	}

	/**
	 *
	 * @return string SQL statement string
	 */
	public function __toString()
	{
		return $this->sql;
	}

	public function getStatementBuilder()
	{
		return $this->builder;
	}

	/**
	 * Set a SELECT statement result column
	 *
	 * @param integer $index
	 * @param integer|ColumnStructure $data
	 *
	 * @note A result column can only be set on top-level context
	 */
	public function setResultColumn($index, $data)
	{
		if ($this->resultColumnAliases->count() > 1)
			return;

		$this->resultColumns->setColumn($index, $data);
	}

	/**
	 * Set the statement type
	 *
	 * @param integer $type
	 *
	 * @note The statement type can only be set on top-level context
	 */
	public function setStatementType($type)
	{
		if ($this->resultColumnAliases->count() > 1)
			return;
		$this->statementType = $type;
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\StructureResolver::findColumn()
	 */
	public function findColumn($path)
	{
		if ($this->resultColumnAliases->isEmpty())
			$this->resultColumnAliases->push(new \ArrayObject());

		if ($this->resultColumnAliases->offsetExists($path))
			return $this->resultColumnAliases->offsetGet($path);

		return $this->structureResolver->findColumn($path);
	}

	/**
	 *
	 * @param string $alias
	 * @param StructureElement|TableReference|ResultColumnReference $reference
	 *
	 * @see \NoreSources\SQL\StructureResolver::Alias()
	 */
	public function setAlias($alias, $reference)
	{
		if ($reference instanceof StructureElement)
		{
			return $this->structureResolver->setAlias($alias, $reference);
		}
		else
		{
			if ($this->resultColumnAliases->isEmpty())
				$this->resultColumnAliases->push(new \ArrayObject());

			$this->resultColumnAliases->offsetSet($alias, $reference);
		}
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\StructureResolver::isAlias()
	 */
	public function isAlias($identifier)
	{
		if ($this->resultColumnAliases->count())
		{
			if ($this->resultColumnAliases->offsetExists($identifier))
				return true;
		}

		return $this->structureResolver->isAlias($identifier);
	}

	public function pushResolverContext(StructureElement $pivot = null)
	{
		$this->resultColumnAliases->push(new \ArrayObject());
		$this->structureResolver->pushResolverContext($pivot);
	}

	public function popResolverContext()
	{
		$this->resultColumnAliases->pop();
		$this->structureResolver->popResolverContext();
	}

	/**
	 *
	 * @property-read integer $statementType
	 * @property-read ResultColumnMap $resultColumns
	 * @param string $member
	 * @throws \InvalidArgumentException
	 * @return number|\NoreSources\SQL\ResultColumnMap
	 */
	public function __get($member)
	{
		if ($member == 'statementType')
			return $this->statementType;
		elseif ($member == 'resultColumns')
			return $this->resultColumns;

		throw new \InvalidArgumentException($member);
	}

	/**
	 * Attempt to call StatementBuilder method
	 *
	 * @param string $method
	 *        	Method name
	 * @param array $args
	 *        	Arguments
	 * @throws \BadMethodCallException
	 * @return mixed
	 *
	 * @method string getColumnDescription(ColumnStructure $column)
	 * @method string getTableConstraintDescription(TableStructure, TableConstraint)
	 *
	 */
	public function __call($method, $args)
	{
		if (\method_exists($this->builder, $method))
		{
			return call_user_func_array(array(
				$this->builder,
				$method
			), $args);
		}

		throw new \BadMethodCallException($method);
	}

	/**
	 *
	 * @var \Noresources\Stack Stack of \ArrayObject
	 */
	private $resultColumnAliases;

	/**
	 *
	 * @var StatementBuilder
	 */
	private $builder;
}