<?php
namespace NoreSources\SQL;

use NoreSources as ns;

class StatementContext implements StatementInputData, StatementOutputData
{
	use StatementInputDataTrait;
	use StatementOutputDataTrait;

	/**
	 * SQL statement string
	 *
	 * @var string
	 */
	public $sql;

	/**
	 *
	 * @var integer
	 */
	public $contextFlags;

	/**
	 *
	 * @var StatementBuilder
	 */
	public $builder;

	/**
	 *
	 * @var StructureResolver
	 */
	public $resolver;

	/**
	 *
	 * @param StatementBuilder $builder
	 * @param StructureElement $pivot
	 */
	public function __construct(StatementBuilder $builder, StructureElement $pivot = null)
	{
		$this->initializeStatementInputData(null);
		$this->initializeStatementOutputData(null);
		$this->sql = '';
		$this->contextFlags = 0;
		$this->builder = $builder;
		$this->resolver = new StructureResolver($pivot);
		$this->resultColumnAliases = new ns\Stack();
	}

	/**
	 *
	 * @return string SQL statement string
	 */
	public function __toString()
	{
		return $this->sql;
	}

	/**
	 * Set a SELECT statement result column
	 *
	 * @param integer $index
	 * @param integer|TableColumnStructure $data
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

		return $this->resolver->findColumn($path);
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
			return $this->resolver->setAlias($alias, $reference);
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

		return $this->resolver->isAlias($identifier);
	}

	public function pushResolverContext(StructureElement $pivot = null)
	{
		$this->resultColumnAliases->push(new \ArrayObject());
		$this->resolver->pushResolverContext($pivot);
	}

	public function popResolverContext()
	{
		$this->resultColumnAliases->pop();
		$this->resolver->popResolverContext();
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
	 * Attemp to call StatementBuilder or StructureResolver method
	 *
	 * @param string $method
	 *        	Method name
	 * @param array $args
	 *        	Arguments
	 * @throws \BadMethodCallException
	 * @return mixed
	 *
	 * @method string getColumnDescription(TableColumnStructure $column)
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
		elseif (\method_exists($this->resolver, $method))
		{
			return call_user_func_array(array(
				$this->resolver,
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
}