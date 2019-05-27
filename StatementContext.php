<?php

namespace NoreSources\SQL;

use NoreSources as ns;
use NoreSources\ArrayUtil;
use NoreSources\Creole\PreformattedBlock;
use NoreSources\SQL\Constants as K;

class StatementContext
{

	/**
	 * Substiture parameter expression by its value
	 * @var integer
	 */
	const PARAMETER_SUBSTITUTION = K::STATEMENT_PARAMETER_SUBSTITUTION;

	public $flags;

	/**
	 * @var StatementBuilder
	 */
	public $builder;

	/**
	 * @var StructureResolver
	 */
	public $resolver;

	public function __construct(StatementBuilder $builder, StructureResolver $resolver)
	{
		$this->flags = 0;
		$this->builder = $builder;
		$this->resolver = $resolver;
		$this->parameters = new \ArrayObject();
		$this->parameterCount = 0;
		$this->aliases = new \ArrayObject();
	}

	/**
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\StructureResolver::findColumn()
	 */
	public function findColumn($path)
	{
		if (ns\ArrayUtil::keyExists($this->aliases, $path))
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
		return ns\ArrayUtil::keyExists($this->aliases, $identifier) || $this->resolver->isAlias($identifier);
	}

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
		
		if (ns\ArrayUtil::keyExists($this->parameters, $name))
		{
			$normalized = $this->parameters[$name];
		}
		else
		{
			if (!$this->builder->isValidParameterName($name))
			{
				$normalized = $this->builder->normalizeParameterName($name, $this);
			}

			$this->parameters[$name] = array (
					"name" => $normalized,
					'value' => null,
					'indexes' => array ()
			);
		}

		$p = &$this->parameters[$normalized];
		$p['indexes'][] = $index;
		
		if ($this->flags & self::PARAMETER_SUBSTITUTION)
		{
			$e = $p['value'];
			if ($e instanceof Expression)
			{
				return $e->buildExpression($this);
			}

			throw new \Exception('Invalid substitution expression for parameter ' . $name);
		}
		else
		{
			return $this->builder->getParameter($p['name'], $index);
		}
	}

	public function getParameters()
	{
		return $this->parameters->getArrayCopy();
	}
		
	/**
	 * Attemp to call StatementBuilder or StructureResolver method
	 * @param string $method Method name
	 * @param array $args Arguments
	 * @throws \BadMethodCallException
	 * @return mixed
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

	private $parameters;

	private $parameterCount;

	private $aliases;
}