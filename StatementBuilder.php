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

	public static function canonicalName($name, StatementBuilder $builder, TableColumnStructure $structure)
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
			
			$this->parts['columns']->append(new ColumnReference($path, $alias));
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
		$resolver->useExceptions = true;
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
		
		return $s;
	}

	private $parts;
}

abstract class StatementBuilder
{

	abstract function escapeString($value);

	abstract function escapeIdentifier($identifier);

	abstract function featureSupport($feature);

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