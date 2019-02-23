<?php

namespace NoreSources\SQL;

use NoreSources as ns;
use NoreSources\ArrayUtil;

class Statement
{

	public function __toString()
	{
		return $this->sqlString;
	}

	private $sqlString;
}

interface StatementElementDescription
{

	function buildStatement(StatementBuilder $builder, StructureResolver $resolver);
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
		$resolver = new StructureResolver($referenceStructure);
		return $this->buildStatement($builder, $resolver);
	}
}

class TableReference
{

	public $path;

	public $alias;

	public function __construct($path, $alias)
	{
		$this->path = $path;
		$this->alias = $alias;
	}
}

class ColumnReference
{

	public $path;

	public $alias;

	public $flags;

	public function __construct($path, $alias)
	{
		$this->path = $path;
		$this->alias = $alias;
	}
}

class Join
{

	/**
	 *
	 * @var TableReference
	 */
	public $table;

	public $type;
}

class OrderBy
{

	public $path;

	public $direction;

	public $collation;

	public function __construct($path, $direction = K::ORDERING_ASC, $collation = null)
	{
		$this->path = $path;
		$this->direction = $direction;
		$this->collation = $collation;
	}
}

class SelectQuery extends QueryDescription
{

	public function __construct($table, $alias = null)
	{
		$this->parts = array (
				'columns' => new \ArrayObject(),
				'table' => new TableReference($table, $alias),
				'joins' => new \ArrayObject(),
				'where' => null,
				'groupby' => null,
				'having' => null,
				'orderby' => new \ArrayObject(),
				'limit' => array (
						'count' => 0,
						'offset' => 0 
				) 
		);
	}

	public function columns(/*...*/ )
	{
		$args = func_get_args();
		foreach ($args as $arg)
		{
			$path = null;
			$alias = null;
			if (\is_array($arg))
			{
				$path = ArrayUtil::keyValue($arg, 0, null);
				$alias = ArrayUtil::keyValue($arg, 1, null);
			}
			else
			{
				$path = $arg;
			}
			
			$this->parts['columns']->append(new ColumnReference($path, $alias));
		}
		
		return $this;
	}

	public function join($table, $type, $columns)
	{
		return $this;
	}

	public function orderBy($reference, $direction = K::ORDERING_ASC, $collation = null)
	{
		$this->parts['orderby']->offsetSet($reference, new OrderBy($reference, $direction, $collation));
	}

	/**
	 *
	 * @param integer $count
	 * @param integer $offset
	 * @return \NoreSources\SQL\SelectQuery
	 */
	public function limit($count, $offset = 0)
	{
		$this->parts['limit']['count'] = $count;
		$this->parts['limit']['offset'] = $offset;
		return $this;
	}

	public function buildStatement(StatementBuilder $builder, StructureResolver $resolver)
	{
		$tableStructure = $resolver->findTable($this->parts['table']->path);
		
		if ($this->parts['table']->alias)
		{
			$resolver->setAlias($this->parts['table']->alias, $tableStructure);
		}
		
		foreach ($this->parts['joins'] as $join)
		{
			$structure = $resolver->findTable($join->path);
			
			if ($join->alias)
			{
				$resolver->setAlias($join->alias, $structure);
			}
		}
		
		foreach ($this->parts['columns'] as $column)
		{
			$structure = $resolver->findColumn($column->path);
			
			if ($column->alias)
			{
				$resolver->setAlias($column->alias, $structure);
			}
		}
		
		$s = 'SELECT ';
		
		if (count($this->parts['columns']))
		{
			$index = 0;
			foreach ($this->parts['columns'] as $column)
			{
				if ($index > 0)
					$s .= ', ';
				
				$structure = $resolver->findColumn($column->path);
				$s .= $builder->getCanonicalName($structure);
				
				if ($column->alias)
				{
					$s .= ' AS ' . $builder->escapeIdentifier($column->alias);
				}
				
				$index++;
			}
		}
		else
		{
			$s .= '*';
		}
		
		$s .= ' FROM ';
		
		$s .= $builder->getCanonicalName($tableStructure);
		if ($this->parts['table']->alias)
		{
			$s .= ' AS ' . $builder->escapeIdentifier($this->parts['table']->alias);
		}
		
		// JOIN
		
		// WHERE
		
		// GROUP BY
		
		// HAVING
		
		// ORDER BY
		if ($this->parts['orderby']->count())
		{
			foreach ($this->parts['orderby'] as $clause)
			{}
		}
		
		// LIMIT
		if ($this->parts['limit']['count'] > 0)
		{
			$s .= ' LIMIT ' . ($this->parts['limit']['count']);
			
			if ($this->parts['limit']['offset'] > 0)
			{
				$s .= ' OFFSET ' . ($this->parts['limit']['offset']);
			}
		}
		
		return $s;
	}

	private $parts;
}

abstract class StatementBuilder
{

	abstract function escapeString($value);

	abstract function escapeIdentifier($identifier);
	
	abstract function getParameter($name);

	abstract function isFeatureSupported($feature);
	
	public function getLiteral(LiteralExpression $literal)
	{
		if ($literal->type & K::kDataTypeNumber)
			return $literal->value;
		
		return $this->escapeString($literal->value);
	}
	
	public function getCanonicalName(StructureElement $structure)
	{
		$s = $this->escapeIdentifier($structure->getName());
		$p = $structure->parent();
		while ($p && !($p instanceof DatasourceStructure))
		{
			$s = $this->escapeIdentifier($p->getName()) . '.' . $s;
			$p = $p->parent();
		}
		
		return $s;
	}
 
}


class GenericStatementBuilder extends StatementBuilder
{

	public function __construct()
	{
		$this->arameters = new \ArrayObject();
	}
	
	public function escapeString($value)
	{
		return "'" . $value . "'";
	}

	public function escapeIdentifier($identifier)
	{
		return '[' . $identifier . ']';
	}

	public function getParameter($name)
	{
		if (!$this->parameters->offsetExists($name))
		{
			$c = $this->parameters->count() + 1;
			$this->parameters->offsetSet($name, $c);
		}
		
		return '$' . $this->parameters->offsetGet($name);
	}
	
	public function isFeatureSupported($feature)
	{
		return true;
	}
	
	/**
	 * @var \ArrayObject
	 */
	private $parameters;
}