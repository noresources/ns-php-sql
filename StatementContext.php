<?php

namespace NoreSources\SQL;

use NoreSources as ns;
use NoreSources\Container;
use NoreSources\Creole\PreformattedBlock;
use NoreSources\SQL\Constants as K;

class StatementContextParameter
{

	/**
	 * @var string
	 */
	public $normalizedName;

	/**
	 * Positions of the parameter in the SQL statement
	 * @var array
	 */
	public $indexes;

	/**
	 * @var Expression
	 */
	public $defaultValue;

	public function __construct($name)
	{
		$this->normalizedName = $name;
		$this->indexes = array ();
		$this->defaultValue = null;
	}

	public function __toString()
	{
		return $this->normalizedName . ' (' . implode(', ', $this->indexes) . ')';
	}
}

class StatementContextParameterMap extends \ArrayObject
{

	public function __construct($a = array ())
	{
		parent::__construct($a);
	}

	public function __toString()
	{
		$s = '';
		foreach ($this as $key => $value)
		{
			$s .= $key . ' -> ' . $value . "\n";
		}
		return $s;
	}
}

class StatementContext
{
	/**
	 * @var integer
	 */
	public $flags;

	/**
	 * @var StatementBuilder
	 */
	public $builder;

	/**
	 * @var StructureResolver
	 */
	public $resolver;

	public function __construct(StatementBuilder $builder, StructureResolver $resolver = null)
	{
		$this->flags = 0;
		$this->builder = $builder;
		if ($resolver instanceof StructureResolver)
			$this->resolver = $resolver;
		else
			$this->resolver = new StructureResolver();
		$this->parameters = new StatementContextParameterMap();
		$this->parameterCount = 0;
		$this->aliases = new \ArrayObject();
	}

	/**
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\StructureResolver::findColumn()
	 */
	public function findColumn($path)
	{
		if (ns\Container::keyExists($this->aliases, $path))
			return $this->aliases[$path];

		return $this->resolver->findColumn($path);
	}

	/**
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
			$this->aliases[$alias] = $reference;
		}
	}

	/**
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\StructureResolver::isAlias()
	 */
	public function isAlias($identifier)
	{
		return ns\Container::keyExists($this->aliases, $identifier) || $this->resolver->isAlias($identifier);
	}

	public function addParameter($name)
	{}

	/**
	 * @param string $name
	 * @throws \Exception
	 * @return string
	 */
	public function getParameter($name)
	{
		$index = $this->parameterCount;
		$normalized = $name;

		$this->parameterCount++;

		if (ns\Container::keyExists($this->parameters, $name))
		{
			$normalized = $this->parameters[$name]->normalizedName;
		}
		else
		{
			if (!$this->builder->isValidParameterName($name))
			{
				$normalized = $this->builder->normalizeParameterName($name, $this);
			}

			$this->parameters[$name] = new StatementContextParameter($normalized);
		}

		$p = &$this->parameters[$name];
		$p->indexes[] = $index;

		return $this->builder->getParameter($p->normalizedName, $index);
	}

	public function getParameters()
	{
		return $this->parameters;
	}

	/**
	 * Attemp to call StatementBuilder or StructureResolver method
	 * @param string $method Method name
	 * @param array $args Arguments
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
			return call_user_func_array(array (
					$this->builder,
					$method
			), $args);
		}
		elseif (\method_exists($this->resolver, $method))
		{
			return call_user_func_array(array (
					$this->resolver,
					$method
			), $args);
		}

		throw new \BadMethodCallException($method);
	}

	/**
	 * @var StatementContextParameterMap
	 */
	private $parameters;

	/**
	 * @var integer
	 */
	private $parameterCount;

	/**
	 * 
	 * @var \ArrayObject
	 */
	private $aliases;
}