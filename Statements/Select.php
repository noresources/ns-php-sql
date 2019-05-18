<?php

// Namespace
namespace NoreSources\SQL;

// Aliases
use NoreSources as ns;
use NoreSources\ArrayUtil;
use NoreSources\Creole\PreformattedBlock;

/**
 * SELECT query result column
 */
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

/**
 * SELECT query JOIN clause
 */
class JoinClause
{

	/**
	 *
	 * @var integer
	 */
	public $operator;

	/**
	 * Table or subquery
	 * @var TableReference|SelectQuery
	 */
	public $subject;

	public $constraints;
}

/**
 * ORDER BY clase
 */
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

/**
 * SELECT query statement
 */
class SelectQuery extends StructureQueryDescription
{

	/**
	 *
	 * @param string $table Table structure path
	 * @param string|null $alias
	 */
	public function __construct($table, $alias = null)
	{
		$this->parts = array (
				self::PART_DISTINCT => false,
				self::PART_COLUMNS => new \ArrayObject(),
				self::PART_TABLE => new TableReference($table, $alias),
				self::PART_JOINS => new \ArrayObject(),
				self::PART_WHERE => null,
				self::PART_GROUPBY => new \ArrayObject(),
				self::PART_HAVING => null,
				self::PART_ORDERBY => new \ArrayObject(),
				self::PART_LIMIT => array (
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
				$this->parts[self::PART_COLUMNS]->append($arg);
			}
			else
			{
				if (ns\ArrayUtil::isArray($arg))
				{
					if (ns\ArrayUtil::isAssociative($arg))
					{
						list ( $expression, $alias ) = each($arg);
					}
					else
					{
						$expression = ArrayUtil::keyValue($arg, 0, null);
						$alias = ArrayUtil::keyValue($arg, 1, null);
					}
				}
				else
				{
					$expression = $arg;
				}
				
				$this->parts[self::PART_COLUMNS]->append(new ResultColumnReference($expression, $alias));
			}
		}
		
		return $this;
	}

	/**
	 *
	 * @param integer|JoinClause $operatorOrJoin
	 * @param string|TableReference|SelectQuery $subject
	 * @param mixed $constraints
	 * @return \NoreSources\SQL\SelectQuery
	 */
	public function join($operatorOrJoin, $subject = null, $constraints = null)
	{
		if ($operatorOrJoin instanceof JoinClause)
		{
			$this->parts[self::PART_JOINS]->append($operatorOrJoin);
		}
		else
		{
			$j = new JoinClause();
			$j->operator = $operatorOrJoin;
			
			if (is_string($subject))
			{
				$j->subject = new TableReference($subject);
			}
			elseif (ns\ArrayUtil::isArray($subject))
			{
				$name = null;
				$alias = null;
				
				if (ns\ArrayUtil::count($subject) == 1)
				{
					list ( $name, $alias ) = each($subject);
				}
				else
				{
					$name = ns\ArrayUtil::keyValue(0, $subject, null);
					$alias = ns\ArrayUtil::keyValue(1, $subject, null);
				}
				
				$j->subject = new TableReference($name, $alias);
			}
			
			if ($constraints instanceof Expression)
			{
				$j->constraints = new UnaryOperatorExpression('ON ', $constraints);
			}
			else
			{
				if (!($j->subject instanceof TableReference))
				{
					throw new \BadMethodCallException();
				}
				
				$left = $this->parts[self::PART_TABLE];
				$right = $j->subject;
				
				$left = $left->alias ? $left->alias : $left->path;
				$right = $right->alias ? $right->alias : $right->path;
				
				$expression = null;
				
				if (\is_string($constraints)) // left.name = right.name
				{
					$expression = new BinaryOperatorExpression('=', new ColumnExpression($left . '.' . $constraints), new ColumnExpression($right . '.' . $constraints));
				}
				elseif (ns\ArrayUtil::isArray($constraints))
				{
					foreach ($constraints as $leftColumn => $righColumn)
					{
						if (is_integer($leftColumn))
							$leftColumn = $righColumn;
						
						$e = new BinaryOperatorExpression('=', new ColumnExpression($left . '.' . $leftColumn), new ColumnExpression($right . '.' . $righColumn));
						
						if ($expression instanceof Expression)
						{
							$expression = new BinaryOperatorExpression(' AND ', $expression, $e);
						}
						else
							$expression = $e;
					}
				}
				
				$j->constraints = new UnaryOperatorExpression('ON ', $expression);
			}
			
			$this->parts[self::PART_JOINS]->append($j);
		}
		
		return $this;
	}

	public function where()
	{
		return $this->whereOrHaving(self::PART_WHERE, func_get_args());
	}

	public function having()
	{
		return $this->whereOrHaving(self::PART_HAVING, func_get_args());
	}

	private function whereOrHaving($part, $args)
	{
		foreach ($args as $arg)
		{
			if (is_string($arg))
			{
				
			}
		}
		
		return $this;
	}

	/**
	 *
	 * @param string (variadic) List of result columns
	 * @return \NoreSources\SQL\SelectQuery
	 */
	public function groupBy()
	{
		for ($i = 0; $i < func_num_args(); $i++)
		{
			$this->parts[self::PART_GROUPBY]->append(new ColumnExpression(func_get_arg($i)));
		}
		
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
		$this->parts[self::PART_ORDERBY]->offsetSet($reference, new OrderBy($reference, $direction, $collation));
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
		$this->parts[self::PART_LIMIT]['count'] = $count;
		$this->parts[self::PART_LIMIT]['offset'] = $offset;
		return $this;
	}

	public function buildExpression(StatementBuilder $builder, StructureResolver $resolver)
	{
		$tableStructure = $resolver->findTable($this->parts[self::PART_TABLE]->path);
		
		# Resolve and build table-related parts
		
		if ($this->parts[self::PART_TABLE]->alias)
		{
			$resolver->setAlias($this->parts[self::PART_TABLE]->alias, $tableStructure);
		}
		
		foreach ($this->parts[self::PART_JOINS] as $join)
		{
			/**
			 *
			 * @var JoinClause $join
			 */
			if ($join->subject instanceof TableReference)
			{
				$structure = $resolver->findTable($join->subject->path);
				if ($join->subject->alias)
				{
					$resolver->setAlias($join->subject->alias, $structure);
				}
			}
		}
		
		$table = $builder->getCanonicalName($tableStructure);
		$tableAlias = $this->parts[self::PART_TABLE]->alias;
		if ($tableAlias)
		{
			$table .= ' AS ' . $builder->escapeIdentifier($tableAlias);
		}
		
		$joins = '';
		foreach ($this->parts[self::PART_JOINS] as $join)
		{
			$joins .= ' ' . $builder->getJoinOperator($join->operator);
			if ($join->subject instanceof TableReference)
			{
				$ts = $resolver->findTable($join->subject->path);
				$joins .= ' ' . $builder->getCanonicalName($ts);
				if ($join->subject->alias)
				{
					$joins .= ' AS ' . $builder->escapeIdentifier($join->subject->alias);
				}
			}
			elseif ($join->subject instanceof Expression)
			{
				$joins .= ' ' . $join->subject->buildStatement($builder, $resolver);
			}
			
			if ($join->constraints instanceof Expression)
				$joins .= ' ' . $join->constraints->buildExpression($builder, $resolver);
		}
		
		if ($builder->getBuilderFlags() & K::BUILDER_EXTENDED_RESULTCOLUMN_ALIAS_RESOLUTION)
		{
			$this->resolveResultColumns($builder, $resolver);
		}
		
		$where = '';
		$having = '';
		
		# Resolve and build column related parts 
		if (!($builder->getBuilderFlags() & K::BUILDER_EXTENDED_RESULTCOLUMN_ALIAS_RESOLUTION))
		{
			$this->resolveResultColumns($builder, $resolver);
		}
		
		$groupBy = '';
		if ($this->parts[self::PART_GROUPBY]->count())
		{
			$groupBy = ' GROUP BY ';
			$a = array ();
			foreach ($this->parts[self::PART_GROUPBY] as $column)
			{
				/**
				 *
				 * @var ColumnExpression $column
				 */
				
				$a[] = $column->buildExpression($builder, $resolver);
			}
			
			$groupBy .= implode(', ', $a);
		}
		
		$s = 'SELECT';
		if ($this->parts[self::PART_DISTINCT])
		{
			$s .= ' DISTINCT';
		}
		
		if ($this->parts[self::PART_COLUMNS]->count())
		{
			$s .= ' ';
			$index = 0;
			foreach ($this->parts[self::PART_COLUMNS] as $column)
			{
				if ($index > 0)
					$s .= ', ';
				
				$expression = $builder->evaluateExpression($column->expression);
				$s .= $expression->buildExpression($builder, $resolver);
				
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
		
		$s .= ' FROM ' . $table;
		
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
		if (strlen($groupBy))
		{
			$s .= ' ' . $groupBy;
			if (strlen($having))
			{
				$s .= ' ' . $having;
			}
		}
		
		// ORDER BY
		if ($this->parts[self::PART_ORDERBY]->count())
		{
			$s .= ' ORDER BY ';
			$o = array ();
			foreach ($this->parts[self::PART_ORDERBY] as $clause) /// @var OrderBy $clause
			{
				$expression = $builder->evaluateExpression($clause->expression);
				
				$o[] = $expression->buildExpression($builder, $resolver) . ' ' . ($clause->direction == K::ORDERING_ASC ? 'ASC' : 'DESC');
			}
			
			$s .= implode(', ', $o);
		}
		
		// LIMIT
		if ($this->parts[self::PART_LIMIT]['count'] > 0)
		{
			$s .= ' LIMIT ' . ($this->parts[self::PART_LIMIT]['count']);
			
			if ($this->parts[self::PART_LIMIT]['offset'] > 0)
			{
				$s .= ' OFFSET ' . ($this->parts[self::PART_LIMIT]['offset']);
			}
		}
		
		return $s;
	}

	protected function resolveResultColumns(StatementBuilder $builder, StructureResolver $resolver)
	{
		foreach ($this->parts[self::PART_COLUMNS] as $column)
		{
			if ($column->alias)
			{
				$resolver->setAlias($column->alias, $builder->evaluateExpression($column->expression));
			}
		}
	}

	private $parts;
	const PART_DISTINCT = 'distinct';
	const PART_COLUMNS = 'columns';
	const PART_TABLE = 'table';
	const PART_JOINS = 'joins';
	const PART_WHERE = 'where';
	const PART_GROUPBY = 'groupby';
	const PART_HAVING = 'having';
	const PART_ORDERBY = 'orderby';
	const PART_LIMIT = 'limit';
}
