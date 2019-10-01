<?php
namespace NoreSources\SQL;

use NoreSources as ns;
use NoreSources\Container;
use NoreSources\Creole\PreformattedBlock;
use NoreSources\SQL\Constants as K;

class StatementContext
{

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

	public function __construct(StatementBuilder $builder, StructureElement $pivot = null)
	{
		$this->contextFlags = 0;
		$this->builder = $builder;
		$this->resolver = new StructureResolver($pivot);
		$this->resultColumnAliases = new ns\Stack();
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