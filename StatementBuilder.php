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

interface QueryDescription
{

	function buildStatement(StatementBuilder $builder, StructureElement $referenceStructure);
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

	public function canonicalName(StatementBuilder $builder, TableStructure $structure)
	{
		$s = $builder->escapeIdentifier($this->path);
		$p = $structure->parent();
		while ($p && !($p instanceof DatasourceStructure))
		{
			$s = $builder->escapeIdentifier($p->getName()) . '.' . $s;
			$p = $p->parent();
		}
		return $s;
	}
}

class ColumnReference
{

	public $path;

	public $alias;

	public function __construct($path, $alias)
	{
		$this->path = $path;
		$this->alias = $alias;
	}
	
	public static function canonicalName ($name, StatementBuilder $builder, TableColumnStructure $structure)
	{
		$s = $builder->escapeIdentifier($name);
		$p = $structure->parent();
		while ($p && !($p instanceof DatasourceStructure))
		{
			$s = $builder->escapeIdentifier($p->getName()) . '.' . $s;
			$p = $p->parent();
		}
		return $s;
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

class SelectQuery implements QueryDescription
{

	public function __construct($table, $alias = null)
	{
		$this->parts = array (
				'columns' => new \ArrayObject(),
				'table' => new TableReference($table, $alias),
				'joins' => new \ArrayObject(),
				'where' => null,
				'having' => null,
				'groupby' => null,
				'orderby' => null 
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
			
			$this->parts['columns']->append (new ColumnReference($path, $alias));
		}
		
		return $this;
	}

	public function join($table, $type, $columns)
	{
		return $this;
	}

	public function buildStatement(StatementBuilder $builder, StructureElement $referenceStructure)
	{
		$resolver = new StructureResolver($referenceStructure);
		$tableStructure = $resolver->findTable($this->parts['table']->path);
		if ($this->parts['table']->alias)
		{
			$resolver->setAlias($this->parts['table']->alias, $tableStructure);
		}
				
		$aliases = array ();
		if ($this->parts['table']->alias)
		{
			$aliases[$this->parts['table']->alias] = $this->parts['table'];
		}
		
		foreach ($this->parts['joins'] as $join)
		{
			if ($join->alias)
			{
				$aliases[$join->alias] = $join;
			}
		}
		
		foreach ($this->parts['columns'] as $column)
		{
			if ($column->alias)
			{
				$aliases[$column->alias] = $column;
			}
		}
		
		$s = 'SELECT ';
		
		if (count($this->parts['columns']))
		{
			foreach ($this->parts['columns'] as $column)
			{
				
			}
		}
		else
		{
			$s .= '*';
		}
		
		$s .= ' FROM ';
		
		$s .= $this->parts['table']->canonicalName($builder, $tableStructore);
		if ($this->parts['table']->alias)
		{
			$s .= ' AS ' . $builder->escapeIdentifier($this->parts['table']->alias);
		}
		
		return $s;
	}

	private $parts;
}

interface StatementBuilder
{

	function escapeString($value);

	function escapeIdentifier($identifier);

	function featureSupport($feature);
}

class GenericStatementBuilder implements StatementBuilder
{

	public function escapeString($value)
	{
		return "'" . $value . "'";
	}

	public function escapeIdentifier($identifier)
	{
		return '[' . $identifier . ']';
	}

	public function featureSupport($feature)
	{
		return true;
	}
}