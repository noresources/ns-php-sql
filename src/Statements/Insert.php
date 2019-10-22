<?php

// Namespace
namespace NoreSources\SQL;

// Aliases
use NoreSources as ns;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\ExpressionEvaluator as X;

class InsertQuery extends Statement implements \ArrayAccess
{

	use ColumnValueTrait;

	public function __construct($table, $alias = null)
	{
		if ($table instanceof TableStructure)
		{
			$table = $table->getPath();
		}

		$this->table = new TableReference($table);
		$this->columnValues = new \ArrayObject();
	}

	public function tokenize(TokenStream &$stream, StatementContext $context)
	{
		$builderFlags = $context->getBuilderFlags(K::BUILDER_DOMAIN_GENERIC);
		$builderFlags |= $context->getBuilderFlags(K::BUILDER_DOMAIN_INSERT);

		$tableStructure = $context->findTable($this->table->path);
		$context->setStatementType(K::QUERY_INSERT);

		/**
		 *
		 * @var TableStructure $tableStructure
		 */

		$context->pushResolverContext($tableStructure);

		$stream->keyword('insert')
			->space()
			->keyword('into')
			->space()
			->identifier($context->getCanonicalName($tableStructure));
		if ($this->table->alias)
		{
			$stream->space()
				->keyword('as')
				->space()
				->identifier($context->escapeIdentifier($this->table->alias));
		}

		$columns = [];
		$values = [];
		$c = $this->columnValues->count();

		if (($c == 0) && ($builderFlags & K::BUILDER_INSERT_DEFAULT_VALUES))
		{
			$stream->space()->keyword('DEFAULT VALUES');
			$context->popResolverContext();
			return $stream;
		}

		foreach ($this->columnValues as $columnName => $value)
		{
			if (!$tableStructure->offsetExists($columnName))
				throw new StatementException($this, 'Invalid column "' . $columnName . '"');

			$columns[] = $context->escapeIdentifier($columnName);
			$column = $tableStructure->offsetGet($columnName);
			/**
			 *
			 * @var TableColumnStructure $column
			 */
			if (!($value instanceof Expression))
			{
				$type = K::DATATYPE_UNDEFINED;
				if ($column->hasProperty(K::COLUMN_PROPERTY_DATA_TYPE))
					$type = $column->getProperty(K::COLUMN_PROPERTY_DATA_TYPE);

				$value = new LiteralExpression($value, $type);
			}

			$values[] = $value;
		}

		if ($c == 0)
		{
			foreach ($tableStructure as $name => $column)
			{
				/**
				 *
				 * @var TableColumnStructure $column
				 */

				if ($column->hasProperty(K::COLUMN_PROPERTY_DEFAULT_VALUE))
				{
					$c++;
					$columns[] = $context->escapeIdentifier($name);
					if ($builderFlags & K::BUILDER_INSERT_DEFAULT_KEYWORD)
					{
						$values[] = new KeywordExpression(K::KEYWORD_DEFAULT);
					}
					else
					{
						$x = ExpressionEvaluator::evaluate(
							$column->getProperty(K::COLUMN_PROPERTY_DEFAULT_VALUE));
						$values[] = $x;
					}
				}
			}
		}

		if ($c == 0)
			throw new StatementException($this, 'No column value');

		$stream->space()->text('(');
		$c = 0;
		foreach ($columns as $column)
		{
			if ($c)
				$stream->text(',')->space();
			$stream->identifier($column);
			$c++;
		}

		$stream->text(')')
			->space()
			->keyword('VALUES')
			->space()
			->text('(');
		$c = 0;
		foreach ($values as $value)
		{
			if ($c)
				$stream->text(',')->space();

			$stream->expression($value, $context);
			$c++;
		}

		$stream->text(')');

		$context->popResolverContext();
		return $stream;
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\Expression::traverse()
	 */
	public function traverse($callable, StatementContext $context, $flags = 0)
	{
		call_user_func($callable, $this, $context, $flags);
		$this->traverseColumnValues($callable, $context, $flags);
	}

	/**
	 *
	 * @var TableReference
	 */
	private $table;
}
