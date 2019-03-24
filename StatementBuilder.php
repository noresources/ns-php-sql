<?php

namespace NoreSources\SQL;

use NoreSources as ns;
use NoreSources\ArrayUtil;
use NoreSources\Creole\PreformattedBlock;

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

class QueryResolver extends StructureResolver
{

	public function __construct(StructureElement $pivot = null)
	{
		parent::__construct($pivot);
		$this->aliases = new \ArrayObject();
	}

	public function findColumn($path)
	{
		echo ($path . PHP_EOL);
		if ($this->aliases->offsetExists($path))
			return $this->aliases->offsetGet($path);
		
		return parent::findColumn($path);
	}

	public function setAlias($alias, $reference)
	{
		echo ('set alias ' . $alias . ' = ' . get_class($reference) . PHP_EOL);
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

class ResultColumnReference
{

	public $expression;

	public $alias;

	public function __construct($expression, $alias)
	{
		$this->expression = $expression;
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

	/**
	 *
	 * @var string
	 */
	public $expression;

	public $direction;

	public $collation;

	public function __construct($expression, $direction = K::ORDERING_ASC, $collation = null)
	{
		$this->expression = $expression;
		$this->direction = $direction;
		$this->collation = $collation;
	}
}

class SelectQuery extends QueryDescription
{

	public function __construct($table, $alias = null)
	{
		$this->parts = array (
				'distinct' => false,
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

	/**
	 *
	 * @return \NoreSources\SQL\SelectQuery
	 */
	public function columns(/*...*/ )
	{
		$args = func_get_args();
		foreach ($args as $arg)
		{
			$expression = null;
			$alias = null;
			if ($arg instanceof ResultColumnReference)
			{
				$this->parts['columns']->append($arg);
			}
			else
			{
				if (\is_array($arg))
				{
					$expression = ArrayUtil::keyValue($arg, 0, null);
					$alias = ArrayUtil::keyValue($arg, 1, null);
				}
				else
				{
					$expression = $arg;
				}
				
				$this->parts['columns']->append(new ResultColumnReference($expression, $alias));
			}
		}
		
		return $this;
	}

	/**
	 *
	 * @param unknown $table
	 * @param unknown $type
	 * @param unknown $columns
	 * @return \NoreSources\SQL\SelectQuery
	 */
	public function join($table, $type, $columns)
	{
		return $this;
	}

	/**
	 *
	 * @param string $reference Result column reference
	 * @param integer $direction
	 * @param mixed $collation
	 * @return \NoreSources\SQL\SelectQuery
	 */
	public function orderBy($reference, $direction = K::ORDERING_ASC, $collation = null)
	{
		$this->parts['orderby']->offsetSet($reference, new OrderBy($reference, $direction, $collation));
		return $this;
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
		
		# Resolve and build table-related parts
		
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
		
		$tables = $builder->getCanonicalName($tableStructure);
		if ($this->parts['table']->alias)
		{
			$tables .= ' AS ' . $builder->escapeIdentifier($this->parts['table']->alias);
		}
		
		$joins = '';
		
		if ($builder->getBuilderFlags() & K::BUILDER_EXTENDED_RESULTCOLUMN_ALIAS_RESOLUTION)
		{
			$this->resolveResultColumns($builder, $resolver);
		}
		
		$where = '';
		$groupBy = '';
		$having = '';
		
		# Resolve and build column related parts 
		if (!($builder->getBuilderFlags() & K::BUILDER_EXTENDED_RESULTCOLUMN_ALIAS_RESOLUTION))
		{
			$this->resolveResultColumns($builder, $resolver);
		}
		
		$s = 'SELECT';
		if ($this->parts['distinct'])
		{
			$s .= ' DISTINCT';
		}
		
		if ($this->parts['columns']->count())
		{
			$s .= ' ';
			$index = 0;
			foreach ($this->parts['columns'] as $column)
			{
				if ($index > 0)
					$s .= ', ';
				
				$expression = $builder->parseExpression($column->expression);
				$s .= $expression->build($builder, $resolver);
				
				if ($column->alias)
				{
					$s .= ' AS ' . $builder->escapeIdentifier($column->alias);
				}
				
				$index++;
			}
		}
		else
		{
			/**
			 *
			 * @todo
			 */
			$s .= ' *';
		}
		
		$s .= ' FROM ' . $tables;
		
		// JOIN
		if (strlen($joins))
		{
			$s .= ' ' . $joins;
		}
		
		// WHERE
		if (strlen($where))
		{
			$s .= ' WHERE ' . $where;
		}
		
		// GROUP BY
		if (iconv_strlen($groupBy))
		{
			$s .= ' ' . $groupBy;
			if (strlen($having))
			{
				$s .= ' ' . $having;
			}
		}
		
		// ORDER BY
		if ($this->parts['orderby']->count())
		{
			$s .= ' ORDER BY ';
			$o = array ();
			foreach ($this->parts['orderby'] as $clause) /// @var OrderBy $clause
			{
				$expression = $builder->parseExpression($clause->expression);
				
				$o[] = $expression->build($builder, $resolver) . ' ' . ($clause->direction == K::ORDERING_ASC ? 'ASC' : 'DESC');
			}
			
			$s .= implode(', ', $o);
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

	protected function resolveResultColumns(StatementBuilder $builder, StructureResolver $resolver)
	{
		foreach ($this->parts['columns'] as $column)
		{
			if ($column->alias)
			{
				$resolver->setAlias($column->alias, $builder->parseExpression($column->expression));
			}
		}
	}

	private $parts;
}

abstract class StatementBuilder
{

	public function __construct($flags = 0)
	{
		$this->builderFlags = $flags;
		$this->parser = null;
	}

	public function getBuilderFlags()
	{
		return $this->builderFlags;
	}

	abstract function escapeString($value);

	abstract function escapeIdentifier($identifier);

	abstract function getParameter($name);

	abstract function isFeatureSupported($feature);

	/**
	 *
	 * @param string $expression
	 * @return \NoreSources\SQL\Expression|\NoreSources\SQL\PreformattedExpression
	 */
	public function parseExpression($expression)
	{
		if ($this->parser instanceof ExpressionParser)
		{
			return $this->parser->parse($expression);
		}
		
		return new PreformattedExpression($expression);
	}

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

	protected function setExpressionParser(ExpressionParser $parser)
	{
		$this->parser = $parser;
	}

	/**
	 *
	 * @var integer
	 */
	private $builderFlags;

	/**
	 *
	 * Expression parser
	 * @var ExpressionParser
	 */
	private $parser;
}

class GenericStatementBuilder extends StatementBuilder
{

	public function __construct()
	{
		$this->parameters = new \ArrayObject();
		$this->setExpressionParser(new ExpressionParser());
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
	 *
	 * @var \ArrayObject
	 */
	private $parameters;
}