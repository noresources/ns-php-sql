<?php

// Namespace
namespace NoreSources\SQL;

// Aliases
use NoreSources as ns;
use NoreSources\Expression as xpr;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Expression\Expression;
use NoreSources\SQL\Expression\Column;
use NoreSources\SQL\Expression\Evaluator;

/**
 * SELECT query result column
 */
class ResultColumnReference
{

	/**
	 * Result column
	 *
	 * @var Expression
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
	 *        	Expression
	 * @param string $alias
	 */
	public function __construct(Expression $expression, $alias)
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

	use xpr\BasicExpressionVisitTrait;

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

	public function tokenize(TokenStream &$stream, BuildContext $context)
	{
		$stream->keyword($context->getJoinOperator($this->operator));

		if ($this->subject instanceof TableReference)
		{
			$ts = $context->findTable($this->subject->path);

			$stream->space()->identifier($context->getCanonicalName($ts));
			if ($this->subject->alias)
			{
				$stream->space()
					->keyword('as')
					->space()
					->identifier($context->escapeIdentifier($this->subject->alias));
			}
		}
		elseif ($this->subject instanceof Expression)
		{
			$stream->space()->expression($this->subject, $context);
		}

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
	 * @return \NoreSources\SQL\JoinClause
	 */
	public function on()
	{
		$c = func_num_args();
		for ($i = 0; $i < $c; $i++)
		{
			$x = func_get_arg($i);
			if (!($x instanceof Expression))
				$x = Evaluator::evaluate($x);
			$this->constraints->append($x);
		}

		return $this;
	}

	private $constraints;
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
	public function __construct($table, $alias = null)
	{
		if ($table instanceof TableStructure)
		{
			$table = $table->getPath();
		}

		$this->parts = [
			self::PART_DISTINCT => false,
			self::PART_COLUMNS => new \ArrayObject(),
			self::PART_TABLE => new TableReference($table, $alias),
			self::PART_JOINS => new \ArrayObject(),
			self::PART_WHERE => new \ArrayObject(),
			self::PART_GROUPBY => new \ArrayObject(),
			self::PART_HAVING => new \ArrayObject(),
			self::PART_ORDERBY => new \ArrayObject(),
			self::PART_LIMIT => [
				'count' => 0,
				'offset' => 0
			]
		];
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
				if (ns\Container::isArray($arg))
				{
					// column expression => alias
					if (ns\Container::isAssociative($arg))
					{
						list ($expression, $alias) = each($arg);
					}
					else // [ column expression, alias ]
					{
						$expression = ns\Container::keyValue($arg, 0, null);
						$alias = ns\Container::keyValue($arg, 1, null);
					}
				}
				else
					$expression = $arg;

				if (!($expression instanceof Expression))
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
			elseif (ns\Container::isArray($subject))
			{
				$name = null;
				$alias = null;

				if (ns\Container::count($subject) == 1)
				{
					list ($name, $alias) = each($subject);
				}
				else
				{
					$name = ns\Container::keyValue(0, $subject, null);
					$alias = ns\Container::keyValue(1, $subject, null);
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
			if (!($x instanceof Expression))
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
		if (!($reference instanceof Expression))
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

	public function tokenize(TokenStream &$stream, BuildContext $context)
	{
		$builderFlags = $context->getBuilderFlags(K::BUILDER_DOMAIN_GENERIC);
		$builderFlags |= $context->getBuilderFlags(K::BUILDER_DOMAIN_SELECT);

		$tableStructure = $context->findTable($this->parts[self::PART_TABLE]->path);

		$context->pushResolverContext($tableStructure);
		$context->setStatementType(K::QUERY_SELECT);

		# Resolve and build table-related parts

		if ($this->parts[self::PART_TABLE]->alias)
		{
			$context->setAlias($this->parts[self::PART_TABLE]->alias, $tableStructure);
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
		$tableAndJoins->identifier($context->getCanonicalName($tableStructure));
		if ($this->parts[self::PART_TABLE]->alias)
		{
			$tableAndJoins->space()
				->keyword('as')
				->space()
				->identifier($context->escapeIdentifier($this->parts[self::PART_TABLE]->alias));
		}

		foreach ($this->parts[self::PART_JOINS] as $join)
		{
			$tableAndJoins->space()->expression($join, $context);
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
					$context->setResultColumn($columnIndex, $structure);
				}
				else
				{
					$context->setResultColumn($columnIndex,
						$column->expression->getExpressionDataType());
				}

				if ($column->alias)
				{
					$stream->space()
						->keyword('as')
						->space()
						->identifier($context->escapeIdentifier($column->alias));
				}
			}
		}
		else
		{
			$columnIndex = 0;
			foreach ($tableStructure as $name => $column)
			{
				$context->setResultColumn($columnIndex, $column);
				$columnIndex++;
			}

			$stream->space()->keyword('*');
		}

		$stream->space()
			->keyword('from')
			->space()
			->stream($tableAndJoins);

		$stream->stream($where);

		// GROUP BY
		if ($this->parts[self::PART_GROUPBY] &&
			ns\Container::count($this->parts[self::PART_GROUPBY]))
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

		// ORDER BY
		if ($this->parts[self::PART_ORDERBY]->count())
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
		if ($this->parts[self::PART_LIMIT]['count'] > 0)
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

		$context->popResolverContext();
		return $stream;
	}

	protected function resolveResultColumns(BuildContext $context)
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
}
