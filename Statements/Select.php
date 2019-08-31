<?php

// Namespace
namespace NoreSources\SQL;

// Aliases
use NoreSources as ns;
use NoreSources\Container;
use NoreSources\SQL\Constants as K;

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
class JoinClause implements Expression
{

	/**
	 * @var integer
	 */
	public $operator;

	/**
	 * Table or subquery
	 * @var TableReference|SelectQuery
	 */
	public $subject;

	public $constraints;

	public function __construct($operator = null, $subject = null, $constraints = null)
	{
		$this->operator = $operator;
		$this->subject = $subject;
		$this->constraints = $constraints;
	}

	public function buildExpression(StatementContext $context)
	{
		$s = $context->getJoinOperator($this->operator);
		if ($this->subject instanceof TableReference)
		{
			$ts = $context->findTable($this->subject->path);
			$s .= ' ' . $context->getCanonicalName($ts);
			if ($this->subject->alias)
			{
				$s .= ' AS ' . $context->escapeIdentifier($this->subject->alias);
			}
		}
		elseif ($this->subject instanceof Expression)
		{
			$s .= ' ' . $this->subject->buildExpression($context);
		}

		if ($this->constraints !== null)
		{
			$e = $this->constraints;
			if (!($e instanceof Expression))
				$e = $context->evaluateExpression($e);

			$s .= ' ON ' . $e->buildExpression($context);
		}

		return $s;
	}

	public function getExpressionDataType()
	{
		return K::DATATYPE_UNDEFINED;
	}

	public function traverse($callable, StatementContext $context, $flags = 0)
	{
		call_user_func($callable, $this, $context, $flags);

		$this->subject->traverse($callable, $context, $flags);

		if ($this->constraints !== null)
		{
			$context->evaluateExpression($this->constraints)->traverse($callable, $context, $flags);
		}
	}
}

/**
 * SELECT query statement
 */
class SelectQuery extends Statement
{

	/**
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
				if (ns\Container::isArray($arg))
				{
					if (ns\Container::isAssociative($arg))
					{
						list ( $expression, $alias ) = each($arg);
					}
					else
					{
						$expression = Container::keyValue($arg, 0, null);
						$alias = Container::keyValue($arg, 1, null);
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
			elseif (ns\Container::isArray($subject))
			{
				$name = null;
				$alias = null;

				if (ns\Container::count($subject) == 1)
				{
					list ( $name, $alias ) = each($subject);
				}
				else
				{
					$name = ns\Container::keyValue(0, $subject, null);
					$alias = ns\Container::keyValue(1, $subject, null);
				}

				$j->subject = new TableReference($name, $alias);
			}

			$j->constraints = $constraints;

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
			$this->parts[$part][] = $arg;
		}

		return $this;
	}

	/**
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
	 * @param string $reference Result column reference
	 * @param integer $direction
	 * @param mixed $collation
	 * @return \NoreSources\SQL\SelectQuery
	 */
	public function orderBy($reference, $direction = K::ORDERING_ASC, $collation = null)
	{
		$this->parts[self::PART_ORDERBY]->offsetSet($reference, array (
				'expression' => $reference,
				'direction' => $direction,
				'collation' => $collation
		));
		return $this;
	}

	/**
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

	public function buildExpression(StatementContext $context)
	{
		$tableStructure = $context->findTable($this->parts[self::PART_TABLE]->path);

		# Resolve and build table-related parts

		if ($this->parts[self::PART_TABLE]->alias)
		{
			$context->setAlias($this->parts[self::PART_TABLE]->alias, $tableStructure);
		}

		foreach ($this->parts[self::PART_JOINS] as $join)
		{
			/**
			 * @var JoinClause $join
			 */
			if ($join->subject instanceof TableReference)
			{
				$structure = $context->findTable($join->subject->path);
				if ($join->subject->alias)
				{
					$context->setAlias($join->subject->alias, $structure);
				}
			}
		}

		$table = $context->getCanonicalName($tableStructure);
		//$table = $tableStructure->getName();
		$tableAlias = $this->parts[self::PART_TABLE]->alias;
		if ($tableAlias)
		{
			$table .= ' AS ' . $context->escapeIdentifier($tableAlias);
		}

		$joins = '';
		foreach ($this->parts[self::PART_JOINS] as $join)
		{
			$joins .= ' ' . $join->buildExpression($context);
		}

		if ($context->getBuilderFlags() & K::BUILDER_EXTENDED_RESULTCOLUMN_ALIAS_RESOLUTION)
		{
			$this->resolveResultColumns($context);
		}

		# Resolve and build column related parts 
		if (!($context->getBuilderFlags() & K::BUILDER_EXTENDED_RESULTCOLUMN_ALIAS_RESOLUTION))
		{
			$this->resolveResultColumns($context);
		}

		$where = '';
		if (ns\Container::count($this->parts[self::PART_WHERE]))
		{
			$where = $this->buildConstraints($this->parts[self::PART_WHERE], $context);
		}

		$having = '';
		if (ns\Container::count($this->parts[self::PART_HAVING]))
		{
			$having = $this->buildConstraints($this->parts[self::PART_HAVING], $context);
		}

		$groupBy = '';
		if ($this->parts[self::PART_GROUPBY]->count())
		{
			$groupBy = ' GROUP BY ';
			$a = array ();
			foreach ($this->parts[self::PART_GROUPBY] as $column)
			{
				$a[] = $column->buildExpression($context);
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

				$expression = $context->evaluateExpression($column->expression);
				$s .= $expression->buildExpression($context);

				if ($column->alias)
				{
					$s .= ' AS ' . $context->escapeIdentifier($column->alias);
				}

				$index++;
			}
		}
		else
		{
			/**
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
			foreach ($this->parts[self::PART_ORDERBY] as $clause)
			{
				$expression = $context->evaluateExpression($clause['expression']);

				$o[] = $expression->buildExpression($context) . ' ' . ($clause['direction'] == K::ORDERING_ASC ? 'ASC' : 'DESC');
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

	public function traverse($callable, StatementContext $context, $flags = 0)
	{
		call_user_func($callable, $this, $context, $flags);

		foreach ($this->parts[self::PART_COLUMNS] as $resultColumn)
		{
			$context->evaluateExpression($resultColumn->expression)->traverse($callable, $context, $flags);
		}

		foreach ($this->parts[self::PART_JOINS] as $join)
		{
			$join->traverse($callable, $context, $flags);
		}

		if ($this->parts[self::PART_WHERE] instanceof Expression)
		{
			$this->parts[self::PART_WHERE]->traverse($callable, $context, $flags);
		}

		foreach ($this->parts[self::PART_GROUPBY] as $group)
		{
			$group->traverse($callable, $context, $flags);
		}

		if ($this->parts[self::PART_HAVING] instanceof Expression)
		{
			$this->parts[self::PART_HAVING]->traverse ($callable, $context, $flags);
		}
		
		foreach ($this->parts[self::PART_ORDERBY] as $clause) 
		{
			$context->evaluateExpression($clause['expression'])->traverse($callable, $context, $flags);
		}
	}
	
	protected function buildConstraints($constraints, StatementContext $context)
	{
		$c = null;
		foreach ($constraints as $constraint)
		{
			$e = $context->evaluateExpression($constraint);
			if ($c instanceof Expression)
				$c = new BinaryOperatorExpression(' AND ', $c, $e);
			else
				$c = $e;
		}

		return $c->buildExpression($context);
	}

	protected function resolveResultColumns(StatementContext $context)
	{
		foreach ($this->parts[self::PART_COLUMNS] as $column)
		{
			if ($column->alias)
			{
				$context->setAlias($column->alias, $context->evaluateExpression($column->expression));
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
