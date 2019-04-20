<?php

namespace NoreSources\SQL;

esultCuse NoreSources as ns;
use NoreSources\ArrayUtil;
use NoreSources\Creole\PreformattedBlock;

/**
 * SQL statement
 *
 */
class Statement
{

	public function __toString()
	{
		return $this->sqlString;
	}

	private $sqlString;
}

/**
 * Describe a SQL statement element
 */
interface StatementElementDescription
{

	/**
	 * @param StatementBuilder $builder
	 * @param StructureResolver $resolver
	 * @return string
	 */
	function buildStatement(StatementBuilder $builder, StructureResolver $resolver);
}

class QueryResolver extends StructureResolver
{

	public function __construct(StructureElement $pivot = null)
	{
		parent::__construct($pivot);
		$this->aliases = new \ArrayObject();
	}

	public function findColumn($path)
	{
		if ($this->aliases->offsetExists($path))
			return $this->aliases->offsetGet($path);
		
		return parent::findColumn($path);
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
			parent::setAlias($alias, $reference);
		}
		else
		{
			$this->aliases[$alias] = $reference;
		}
	}

	public function isAlias($identifier)
	{
		return $this->aliases->offsetExists($identifier) || parent::isAlias($identifier);
	}

	/**
	 *
	 * @var \ArrayObject
	 */
	private $aliases;
}

abstract class QueryDescription implements StatementElementDescription
{

	/**
	 *
	 * @param StatementBuilder $builder
	 * @param StructureElement $referenceStructure
	 * @return string
	 */
	function build(StatementBuilder $builder, StructureElement $referenceStructure)
	{
		$resolver = new QueryResolver($referenceStructure);
		return $this->buildStatement($builder, $resolver);
	}
}

/**
 * SQL Table reference in a SQL query
 */
class TableReference
{

	/**
	 * Table path
	 * @var string
	 */
	public $path;

	/**
	 * @var string
	 */
	public $alias;

	public function __construct($path, $alias = null)
	{
		$this->path = $path;
		$this->alias = $alias;
	}
}

