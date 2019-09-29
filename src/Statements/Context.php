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
		$this->resultColumnAliases = new \ArrayObject();
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\StructureResolver::findColumn()
	 */
	public function findColumn($path)
	{
		if (ns\Container::keyExists($this->resultColumnAliases, $path))
			return $this->resultColumnAliases[$path];

		return $this->resolver->findColumn($path);
	}

	/**
	 *
	 * @param string $alias
	 * @param StructureElement|TableReference|ResultColumnReference $reference
	 *
	 * @see \NoreSources\SQL\StructureResolver::setAlias()
	 */
	public function setAlias($alias, $reference)
	{
		if ($reference instanceof StructureElement)
		{
			return $this->resolver->setAlias($alias, $reference);
		}
		else
		{
			$this->resultColumnAliases[$alias] = $reference;
		}
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\StructureResolver::isAlias()
	 */
	public function isAlias($identifier)
	{
		return ns\Container::keyExists($this->resultColumnAliases, $identifier) ||
			$this->resolver->isAlias($identifier);
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
	 * @var \ArrayObject
	 */
	private $resultColumnAliases;
}