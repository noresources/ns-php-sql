<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */

// Namespace
namespace NoreSources\SQL\Statement;

// Aliases
use NoreSources\Container;
use NoreSources\TypeDescription;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Expression\Column;
use NoreSources\SQL\Expression\Evaluator;
use NoreSources\SQL\Expression\ExpressionReturnType;
use NoreSources\SQL\Expression\TableReference;
use NoreSources\SQL\Expression\TokenStream;
use NoreSources\SQL\Expression\TokenStreamContext;
use NoreSources\SQL\Expression\TokenizableExpression;
use NoreSources\SQL\Structure\TableStructure;

/**
 * SELECT query result column
 */
class ResultColumnReference
{

	/**
	 * Result column
	 *
	 * @var TokenizableExpression
	 */
	public $expression;

	/**
	 *
	 * @var string Result column alias
	 */
	public $alias;

	/**
	 *
	 * @param
	 *        	TokenizableExpression
	 * @param string $alias
	 */
	public function __construct(TokenizableExpression $expression, $alias)
	{
		$this->expression = $expression;
		$this->alias = $alias;
	}
}

/**
 * SELECT query JOIN clause
 */
class JoinClause implements TokenizableExpression
{

	/**
	 *
	 * @var integer
	 */
	public $operator;

	/**
	 * Table or subquery
	 *
	 * @var TableReference|SelectQuery
	 */
	public $subject;

	public function __construct($operator = null, TableReference $subject = null /*, on ...*/)
	{
		$this->operator = $operator;
		$this->subject = $subject;
		$this->constraints = new \ArrayObject();

		$args = func_get_args();
		array_shift($args);
		array_shift($args);

		call_user_func_array([
			$this,
			'on'
		], $args);
	}

	public function tokenize(TokenStream $stream, TokenStreamContext $context)
	{
		$stream->keyword($context->getStatementBuilder()
			->getJoinOperator($this->operator));

		$stream->space()->expression($this->subject, $context);

		if ($this->constraints->count())
		{
			$stream->space()
				->keyword('on')
				->space()
				->constraints($this->constraints, $context);
		}

		return $stream;
	}

	public function getExpressionDataType()
	{
		return K::DATATYPE_UNDEFINED;
	}

	/**
	 *
	 * @param
	 *        	... List of constraint expressions
	 * @return \NoreSources\SQL\Statement\JoinClause
	 */
	public function on()
	{
		$c = func_num_args();
		for ($i = 0; $i < $c; $i++)
		{
			$x = func_get_arg($i);
			if (!($x instanceof TokenizableExpression))
				$x = Evaluator::evaluate($x);
			$this->constraints->append($x);
		}

		return $this;
	}

	private $constraints;
}

class UnionClause
{

	/**
	 *
	 * @var SelectQuery
	 */
	public $query;

	/**
	 *
	 * @var boolean
	 */
	public $all;

	public function __construct(SelectQuery $q, $all = false)
	{
		$this->query = $q;
		$this->all = $all;
	}
}

/**
 * SELECT query statement
 */
class SelectQuery extends Statement
{

	/**
	 *
	 * @param TableStructure|string $table
	 *        	Table structure path
	 * @param string|null $alias
	 */
	public function __construct($table = null, $alias = null)
	{
		$this->parts = [
			self::PART_DISTINCT => false,
			self::PART_COLUMNS => new \ArrayObject(),
			self::PART_TABLE => null,
			self::PART_JOINS => new \ArrayObject(),
			self::PART_WHERE => new \ArrayObject(),
			self::PART_GROUPBY => new \ArrayObject(),
			self::PART_HAVING => new \ArrayObject(),
			self::PART_ORDERBY => new \ArrayObject(),
			self::PART_UNION => [],
			self::PART_LIMIT => [
				'count' => 0,
				'offset' => 0
			]
		];

		if ($table)
			$this->table($table, $alias);
	}

	/**
	 *
	 * @param TableStructure|string $table
	 *        	Table structure path
	 * @param string|null $alias
	 */
	public function table($table, $alias = null)
	{
		if ($table instanceof TableStructure)
			$table = $table->getPath();

		if (!\is_string($table))
			throw new \InvalidArgumentException(
				'Invalid type for $table argument. ' . TableStructure::class .
				' or string expected. Got ' . TypeDescription::getName($table));

		$this->parts[self::PART_TABLE] = new TableReference($table, $alias);
		return $this;
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
				if (Container::isArray($arg))
				{
					// column expression => alias
					if (Container::isAssociative($arg))
					{
						list ($expression, $alias) = each($arg);
					}
					else // [ column expression, alias ]
					{
						$expression = Container::keyValue($arg, 0, null);
						$alias = Container::keyValue($arg, 1, null);
					}
				}
				else
					$expression = $arg;

				if (!($expression instanceof TokenizableExpression))
					$expression = Evaluator::evaluate($expression);

				$this->parts[self::PART_COLUMNS]->append(
					new ResultColumnReference($expression, $alias));
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
	public function join($operatorOrJoin, $subject = null /*, $constraints */)
	{
		if ($operatorOrJoin instanceof JoinClause)
		{
			$this->parts[self::PART_JOINS]->append($operatorOrJoin);
		}
		else
		{
			if (is_string($subject))
			{
				$subject = new TableReference($subject);
			}
			elseif (Container::isArray($subject))
			{
				$name = null;
				$alias = null;

				if (Container::count($subject) == 1)
				{
					list ($name, $alias) = each($subject);
				}
				else
				{
					$name = Container::keyValue(0, $subject, null);
					$alias = Container::keyValue(1, $subject, null);
				}

				$subject = new TableReference($name, $alias);
			}

			$j = new JoinClause($operatorOrJoin, $subject);
			$args = func_get_args();
			array_shift($args);
			array_shift($args);
			call_user_func_array([
				$j,
				'on'
			], $args);

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
		foreach ($args as $x)
		{
			if (!($x instanceof TokenizableExpression))
				$x = Evaluator::evaluate($x);
			$this->parts[$part]->append($x);
		}

		return $this;
	}

	/**
	 *
	 * @param
	 *        	string (variadic) List of result columns
	 * @return \NoreSources\SQL\SelectQuery
	 */
	public function groupBy()
	{
		for ($i = 0; $i < func_num_args(); $i++)
		{
			$this->parts[self::PART_GROUPBY]->append(new Column(func_get_arg($i)));
		}

		return $this;
	}

	public function union(SelectQuery $query, $all = false)
	{
		$this->parts[self::PART_UNION][] = new UnionClause($query, $all);
	}

	/**
	 *
	 * @param string $reference
	 *        	Result column reference
	 * @param integer $direction
	 * @param mixed $collation
	 * @return \NoreSources\SQL\SelectQuery
	 */
	public function orderBy($reference, $direction = K::ORDERING_ASC, $collation = null)
	{
		if (!($reference instanceof TokenizableExpression))
			$reference = Evaluator::evaluate($reference);

		$this->parts[self::PART_ORDERBY]->append(
			[
				'expression' => $reference,
				'direction' => $direction,
				'collation' => $collation
			]);
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

	public function hasUnion()
	{
		return Container::count($this->parts[self::PART_UNION]);
	}

	/**
	 *
	 * @return boolean
	 */
	public function hasLimitClause()
	{
		return ($this->parts[self::PART_LIMIT]['count'] > 0);
	}

	/**
	 *
	 * @return number
	 */
	public function hasOrderingClause()
	{
		return Container::count($this->parts[self::PART_ORDERBY]);
	}

	public function tokenize(TokenStream $stream, TokenStreamContext $context)
	{
		$builderFlags = $context->getStatementBuilder()->getBuilderFlags(K::BUILDER_DOMAIN_GENERIC);
		$builderFlags |= $context->getStatementBuilder()->getBuilderFlags(K::BUILDER_DOMAIN_SELECT);

		$context->setStatementType(K::QUERY_SELECT);

		$table = $this->parts[self::PART_TABLE];
		/**
		 *
		 * @var TableReference $table
		 */
		$tableStructure = null;
		if ($table instanceof TableReference)
		{
			$tableStructure = $context->findTable($table->path);
			$context->pushResolverContext($tableStructure);
		}

		# Resolve and b'from'uild table-related parts

		if ($table instanceof TableReference && $table->alias)
		{
			$context->setAlias($table->alias, $tableStructure);
		}

		foreach ($this->parts[self::PART_JOINS] as $join)
		{
			/**
			 *
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

		$tableAndJoins = new TokenStream();
		if ($table instanceof TableReference)
		{
			$tableAndJoins->identifier(
				$context->getStatementBuilder()
					->getCanonicalName($tableStructure));
			if ($table->alias)
			{
				$tableAndJoins->space()
					->keyword('as')
					->space()
					->identifier($context->getStatementBuilder()
					->escapeIdentifier($table->alias));
			}

			foreach ($this->parts[self::PART_JOINS] as $join)
			{
				$tableAndJoins->space()->expression($join, $context);
			}
		}

		if ($builderFlags & K::BUILDER_SELECT_EXTENDED_RESULTCOLUMN_ALIAS_RESOLUTION)
		{
			$this->resolveResultColumns($context);
		}

		$where = new TokenStream();

		if ($this->parts[self::PART_WHERE]->count())
		{
			$where->space()
				->keyword('where')
				->space()
				->constraints($this->parts[self::PART_WHERE], $context);
		}

		$having = new TokenStream();
		if ($this->parts[self::PART_HAVING]->count())
		{
			$having->space()
				->keyword('having')
				->space()
				->constraints($this->parts[self::PART_HAVING], $context);
		}

		# Resolve columns (inf not yet)
		if (!($builderFlags & K::BUILDER_SELECT_EXTENDED_RESULTCOLUMN_ALIAS_RESOLUTION))
		{
			$this->resolveResultColumns($context);
		}

		// SELECT columns

		$stream->keyword('select');
		if ($this->parts[self::PART_DISTINCT])
		{
			$stream->space()->keyword('DISTINCT');
		}

		if ($this->parts[self::PART_COLUMNS]->count())
		{
			$stream->space();
			$c = 0;
			foreach ($this->parts[self::PART_COLUMNS] as $column)
			{
				$columnIndex = $c;

				if ($c++ > 0)
					$stream->text(',')->space();

				$stream->expression($column->expression, $context);

				if ($column->expression instanceof Column)
				{
					$structure = $context->findColumn($column->expression->path);
					$context->setResultColumn($columnIndex, $structure, $column->alias);
				}
				else
				{
					$type = K::DATATYPE_UNDEFINED;
					if ($column->expression instanceof ExpressionReturnType)
						$type = $column->expression->getExpressionDataType();
					$context->setResultColumn($columnIndex, $type, $column->alias);
				}

				if ($column->alias)
				{
					$stream->space()
						->keyword('as')
						->space()
						->identifier(
						$context->getStatementBuilder()
							->escapeIdentifier($column->alias));
				}
			}
		}
		elseif ($table instanceof TableReference)
		{
			$columnIndex = 0;
			foreach ($tableStructure as $name => $column)
			{
				$context->setResultColumn($columnIndex, $column);
				$columnIndex++;
			}

			$stream->space()->keyword('*');
		}

		if ($table instanceof TableReference)
			$stream->space()
				->keyword('from')
				->space()
				->stream($tableAndJoins);

		$stream->stream($where);

		// GROUP BY
		if ($this->parts[self::PART_GROUPBY] && Container::count($this->parts[self::PART_GROUPBY]))
		{

			$stream->space()
				->keyword('group by')
				->space();

			$c = 0;
			foreach ($this->parts[self::PART_GROUPBY] as $column)
			{
				if ($c++ > 0)
					$stream->text(',')->space();
				$stream->expression($column, $context);
			}
		}

		$stream->stream($having);

		if (Container::count($this->parts[self::PART_UNION]))
		{
			foreach ($this->parts[self::PART_UNION] as $union)
			{
				/**
				 *
				 * @var UnionClause $union
				 */
				if ($union->query->hasLimitClause() || $union->query->hasOrderingClause())
					throw new \LogicException('UNIONed query canont have LIMIT or ORDER BY clause');

				$stream->space()->keyword('union');
				if ($union->all)
					$stream->space()->keyword('all');

				$stream->space()->expression($union->query, $context);
			}
		}

		// ORDER BY
		if ($this->hasOrderingClause())
		{
			$stream->space()
				->keyword('order by')
				->space();
			$c = 0;
			foreach ($this->parts[self::PART_ORDERBY] as $clause)
			{
				if ($c++ > 0)
					$stream->text(',')->space();

				$stream->expression($clause['expression'], $context)
					->space()
					->keyword($clause['direction'] == K::ORDERING_ASC ? 'ASC' : 'DESC');
			}
		}

		// LIMIT
		if ($this->hasLimitClause())
		{
			$stream->space()
				->keyword('limit')
				->space()
				->literal($this->parts[self::PART_LIMIT]['count']);

			if ($this->parts[self::PART_LIMIT]['offset'] > 0)
			{
				$stream->space()
					->keyword('offset')
					->space()
					->literal($this->parts[self::PART_LIMIT]['offset']);
			}
		}

		if ($table instanceof TableReference)
			$context->popResolverContext();
		return $stream;
	}

	protected function resolveResultColumns(TokenStreamContext $context)
	{
		foreach ($this->parts[self::PART_COLUMNS] as $column)
		{
			if ($column->alias)
				$context->setAlias($column->alias, $column->expression);
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

	const PART_UNION = 'union';
}
