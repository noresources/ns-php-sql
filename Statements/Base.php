<?php

// Namespace
namespace NoreSources\SQL;

// Aliases
use NoreSources as ns;
use NoreSources\ArrayUtil;
use NoreSources\Creole\PreformattedBlock;

/**
 * Resolve both StructureElement reference and result column aliases
 *
 */
class StructureQueryResolver extends StructureResolver
{

	/**
	 *
	 * @param StructureElement $pivot
	 */
	public function __construct(StructureElement $pivot = null)
	{
		parent::__construct($pivot);
		$this->aliases = new \ArrayObject();
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\StructureResolver::findColumn()
	 */
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

	/**
	 *
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\StructureResolver::isAlias()
	 */
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


/**
 * SQL Table reference in a SQL query
 */
class TableReference extends TableExpression
{

	/**
	 *
	 * @var string
	 */
	public $alias;

	public function __construct($path, $alias = null)
	{
		parent::__construct($path);
		$this->alias = $alias;
	}

	function buildExpression(StatementBuilder $builder, StructureResolver $resolver)
	{
		$s = parent::buildExpression($builder, $resolver);
		if ($this->alias)
		{
			$s .= ' AS ' . $this->alias;
		}
		
		return $s;
	}
}

