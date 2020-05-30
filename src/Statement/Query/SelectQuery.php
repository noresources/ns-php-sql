<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */

//
namespace NoreSources\SQL\Statement\Query;

use NoreSources\Container;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Expression\Column;
use NoreSources\SQL\Expression\DataRowContainerReference;
use NoreSources\SQL\Expression\Evaluator;
use NoreSources\SQL\Expression\ExpressionReturnTypeInterface;
use NoreSources\SQL\Expression\Table;
use NoreSources\SQL\Expression\TokenStream;
use NoreSources\SQL\Expression\TokenStreamContextInterface;
use NoreSources\SQL\Expression\TokenizableExpressionInterface;
use NoreSources\SQL\Statement\Statement;
use NoreSources\SQL\Statement\StatementException;
use NoreSources\SQL\Statement\Traits\ConstraintExpressionListTrait;
use NoreSources\SQL\Structure\StructureElementContainerInterface;
use NoreSources\SQL\Structure\StructureElementInterface;

/**
 * SELECT query result column
 */
class ResultColumnReference
{

	/**
	 * Result column
	 *
	 * @var TokenizableExpressionInterface
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
	 *        	TokenizableExpressionInterface
	 * @param string $alias
	 */
	public function __construct(TokenizableExpressionInterface $expression, $alias)
	{
		$this->expression = $expression;
		$this->alias = $alias;
	}
}

/**
 * SELECT query statement
 */
class SelectQuery extends Statement
{
	use ConstraintExpressionListTrait;

	/**
	 *
	 * @param TableStructure|string $table
	 *        	Table structure path
	 * @param string|null $alias
	 */
	public function __construct($table = null, $alias = null)
	{
		$this->selectQueryFlags = 0;

		$this->whereConstraints = null;
		$this->havingConstraints = null;
		$this->limitClause = [
			'count' => 0,
			'offset' => 0
		];

		if ($table)
			$this->from($table, $alias);
	}

	/**
	 *
	 * @param boolean $distinct
	 *        	If TRUE, the SELECT query will only report rows with distinct values.
	 * @return \NoreSources\SQL\Statement\Query\SelectQuery
	 */
	public function distinct($distinct = true)
	{
		if ($distinct)
			$this->selectQueryFlags |= K::SELECT_QUERY_DISTINCT;
		else
			$this->selectQueryFlags &= ~K::SELECT_QUERY_DISTINCT;
		return $this;
	}

	/**
	 *
	 * @return SelectQuery
	 */
	public function columns(/*...*/ )
	{
		if (!($this->resultColumns instanceof \ArrayObject))
			$this->resultColumns = new \ArrayObject();
		$args = func_get_args();
		foreach ($args as $arg)
		{
			$expression = null;
			$alias = null;
			if ($arg instanceof ResultColumnReference)
				$this->resultColumns->append($arg);
			else
			{
				if (Container::isArray($arg))
				{
					// column expression => alias
					if (Container::isAssociative($arg))
						list ($expression, $alias) = each($arg);
					else // [ column expression, alias ]
					{
						$expression = Container::keyValue($arg, 0, null);
						$alias = Container::keyValue($arg, 1, null);
					}
				}
				else
					$expression = $arg;

				if (!($expression instanceof TokenizableExpressionInterface))
					$expression = Evaluator::evaluate($expression);

				$this->resultColumns->append(new ResultColumnReference($expression, $alias));
			}
		}

		return $this;
	}

	/**
	 *
	 * @param SelectQuery|TableStructure|ViewStructure|string $from
	 * @param string $alias
	 *        	Target alias
	 * @return \NoreSources\SQL\Statement\Query\SelectQuery
	 */
	public function from($from, $alias = null)
	{
		$this->fromTarget = new DataRowContainerReference($from, $alias);
		return $this;
	}

	/**
	 *
	 * @param integer|JoinClause $operatorOrJoin
	 * @param string|TableReference|SelectQuery $subject
	 * @param mixed $constraints
	 * @return SelectQuery
	 */
	public function join($operatorOrJoin, $subject = null /*, $constraints */)
	{
		if (!($this->joins instanceof \ArrayObject))
			$this->joins = new \ArrayObject();

		if ($operatorOrJoin instanceof JoinClause)
		{
			$this->joins->append($operatorOrJoin);
			return $this;
		}

		$alias = null;
		$target = $subject;
		if (Container::isArray($subject))
		{
			if (Container::count($subject) == 1)
				list ($target, $alias) = Container::getFirstElement($subject);
			else
			{
				$target = Container::keyValue($subject, 0);
				$alias = Container::keyValue($subject, 1);
			}
		}

		$j = new JoinClause($operatorOrJoin, new DataRowContainerReference($target, $alias));
		$args = func_get_args();
		array_shift($args);
		array_shift($args);
		call_user_func_array([
			$j,
			'on'
		], $args);

		$this->joins->append($j);

		return $this;
	}

	/**
	 *
	 * @param Evaluable $args...
	 *        	A list of evaluable expressions
	 * @return SelectQuery
	 */
	public function where()
	{
		if (!($this->whereConstraints instanceof \ArrayObject))
			$this->whereConstraints = new \ArrayObject();
		return $this->addConstraints($this->whereConstraints, func_get_args());
	}

	/**
	 *
	 * @param Evaluable $args...
	 *        	A list of evaluable expressions
	 *
	 * @return SelectQuery
	 */
	public function having()
	{
		if (!($this->havingConstraints instanceof \ArrayObject))
			$this->havingConstraints = new \ArrayObject();
		return $this->addConstraints($this->havingConstraints, func_get_args());
	}

	/**
	 *
	 * @param
	 *        	string (variadic) List of result columns
	 * @return SelectQuery
	 */
	public function groupBy()
	{
		if (!($this->groupByClauses instanceof \ArrayObject))
			$this->groupByClauses = new \ArrayObject();

		for ($i = 0; $i < func_num_args(); $i++)
			$this->groupByClauses->append(new Column(func_get_arg($i)));

		return $this;
	}

	/**
	 *
	 * @param SelectQuery $query
	 * @param boolean $all
	 *        	UNION ALL
	 * @return \NoreSources\SQL\Statement\Query\SelectQuery
	 */
	public function union(SelectQuery $query, $all = false)
	{
		if (!($this->unions instanceof \ArrayObject))
			$this->unions = new \ArrayObject();
		$this->unions->append(new UnionClause($query, $all));
		return $this;
	}

	/**
	 *
	 * @param string $reference
	 *        	Result column reference
	 * @param integer $direction
	 * @param mixed $collation
	 * @return SelectQuery
	 */
	public function orderBy($reference, $direction = K::ORDERING_ASC, $collation = null)
	{
		if (!($reference instanceof TokenizableExpressionInterface))
			$reference = Evaluator::evaluate($reference);

		if (!($this->orderByClauses instanceof \ArrayObject))
			$this->orderByClauses = new \ArrayObject();

		$this->orderByClauses->append(
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
	 * @return SelectQuery
	 */
	public function limit($count, $offset = 0)
	{
		$this->limitClause['count'] = $count;
		$this->limitClause['offset'] = $offset;
		return $this;
	}

	/**
	 *
	 * @return boolean
	 */
	public function hasUnion()
	{
		return ($this->unions && $this->unions->count());
	}

	/**
	 *
	 * @return boolean
	 */
	public function hasLimitClause()
	{
		return ($this->limitClause['count'] > 0);
	}

	/**
	 *
	 * @return number
	 */
	public function hasOrderingClause()
	{
		return ($this->orderByClauses && $this->orderByClauses->count());
	}

	/**
	 *
	 * @return integer Query flags
	 */
	public function getSelectQueryFlags()
	{
		return $this->selectQueryFlags;
	}

	public function tokenize(TokenStream $stream, TokenStreamContextInterface $context)
	{
		$builderFlags = $context->getStatementBuilder()->getBuilderFlags(K::BUILDER_DOMAIN_GENERIC);
		$builderFlags |= $context->getStatementBuilder()->getBuilderFlags(K::BUILDER_DOMAIN_SELECT);

		$context->setStatementType(K::QUERY_SELECT);

		/**
		 *
		 * @var TableReference $table
		 */
		$targetStructure = null;
		if ($this->fromTarget instanceof DataRowContainerReference)
		{
			if ($this->fromTarget->expression instanceof Table)
			{
				$targetStructure = $context->findTable($this->fromTarget->expression->path);
				$context->pushResolverContext($targetStructure);
			}
		}

		// Resolve and b'from'uild table-related parts
		$targetAndJoins = new TokenStream();
		if ($this->fromTarget instanceof DataRowContainerReference)
		{
			// Pass 1: register aliases
			if ($this->fromTarget->alias)
			{
				$reference = ($targetStructure instanceof StructureElementInterface) ? $targetStructure : $this->fromTarget;
				$context->setAlias($this->fromTarget->alias, $reference);
			}

			if ($this->joins instanceof \ArrayObject)
			{
				foreach ($this->joins as $join)

				{

					if ($join->subject->alias)
					{
						$structure = $join->subject;
						if ($join->subject->expression instanceof Table)
							$structure = $context->findTable($join->subject->expression->path);
						$context->setAlias($join->subject->alias, $structure);
					}
				}
			}

			// Pass 2: tokenize
			$targetAndJoins->expression($this->fromTarget, $context);

			if ($this->joins instanceof \ArrayObject)
				foreach ($this->joins as $join)
				{
					$targetAndJoins->space()->expression($join, $context);
				}
		}

		if ($builderFlags & K::BUILDER_SELECT_EXTENDED_RESULTCOLUMN_ALIAS_RESOLUTION)
		{
			$this->resolveResultColumns($context);
		}

		$where = new TokenStream();

		if ($this->whereConstraints && $this->whereConstraints->count())
		{
			$where->space()
				->keyword('where')
				->space()
				->constraints($this->whereConstraints, $context);
		}

		$having = new TokenStream();
		if ($this->havingConstraints && $this->havingConstraints->count())
		{
			$having->space()
				->keyword('having')
				->space()
				->constraints($this->havingConstraints, $context);
		}

		// Resolve columns (inf not yet)
		if (!($builderFlags & K::BUILDER_SELECT_EXTENDED_RESULTCOLUMN_ALIAS_RESOLUTION))
		{
			$this->resolveResultColumns($context);
		}

		// SELECT columns

		$stream->keyword('select');
		if ($this->selectQueryFlags & K::SELECT_QUERY_DISTINCT)
		{
			$stream->space()->keyword('DISTINCT');
		}

		if ($this->resultColumns && $this->resultColumns->count())
		{
			$stream->space();
			$c = 0;
			foreach ($this->resultColumns as $column)
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
					if ($column->expression instanceof ExpressionReturnTypeInterface)
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
		elseif ($this->fromTarget instanceof DataRowContainerReference)
		{
			if ($targetStructure instanceof StructureElementContainerInterface)
			{
				$columnIndex = 0;
				foreach ($targetStructure as $name => $column)
				{
					$context->setResultColumn($columnIndex, $column);
					$columnIndex++;
				}
			}

			$stream->space()->keyword('*');
		}

		if ($this->fromTarget instanceof DataRowContainerReference)
			$stream->space()
				->keyword('from')
				->space()
				->stream($targetAndJoins);

		$stream->stream($where);

		// GROUP BY
		if ($this->groupByClauses && Container::count($this->groupByClauses))
		{

			$stream->space()
				->keyword('group by')
				->space();

			$c = 0;
			foreach ($this->groupByClauses as $column)
			{
				if ($c++ > 0)
					$stream->text(',')->space();
				$stream->expression($column, $context);
			}
		}

		$stream->stream($having);

		if ($this->hasUnion())
		{
			foreach ($this->unions as $union)
			{
				/**
				 *
				 * @var UnionClause $union
				 */
				if ($union->query->hasLimitClause() || $union->query->hasOrderingClause())
					throw new StatementException($this,
						'UNIONed query canont have LIMIT or ORDER BY clause');

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
			if ($this->orderByClauses instanceof \ArrayObject)
				foreach ($this->orderByClauses as $clause)
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
				->literal($this->limitClause['count']);

			if ($this->limitClause['offset'] > 0)
			{
				$stream->space()
					->keyword('offset')
					->space()
					->literal($this->limitClause['offset']);
			}
		}

		if ($this->fromTarget instanceof DataRowContainerReference)
		{
			if ($targetStructure instanceof StructureElementContainerInterface)
				$context->popResolverContext();
		}
		return $stream;
	}

	protected function resolveResultColumns(TokenStreamContextInterface $context)
	{
		if (!($this->resultColumns instanceof \ArrayObject))
			return;

		foreach ($this->resultColumns as $column)
		{
			if ($column->alias)
				$context->setAlias($column->alias, $column->expression);
		}
	}

	/**
	 *
	 * @var integer
	 */
	private $selectQueryFlags;

	/**
	 *
	 * @var DataRowContainerReference
	 */
	private $fromTarget;

	/**
	 *
	 * @var \ArrayObject
	 */
	private $resultColumns;

	/**
	 *
	 * @var \ArrayObject
	 */
	private $joins;

	/**
	 *
	 * @var \ArrayObject
	 */
	private $whereConstraints;

	/**
	 *
	 * @var \ArrayObject
	 */
	private $groupByClauses;

	/**
	 *
	 * @var \ArrayObject
	 */
	private $havingConstraints;

	/**
	 *
	 * @var \ArrayObject
	 */
	private $orderByClauses;

	/**
	 *
	 * @var array
	 */
	private $limitClause;

	/**
	 *
	 * @var \ArrayObject
	 */
	private $unions;
}
