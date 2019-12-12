<?php

// Namespace
namespace NoreSources\SQL\Statement;

// Aliases
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\BuildContext;
use NoreSources\SQL\Statement;
use NoreSources\SQL\StatementException;
use NoreSources\SQL\TableStructure;
use NoreSources\SQL\TokenStream;
use NoreSources\SQL\Expression\Evaluator;
use NoreSources\SQL\Expression\Expression;
use NoreSources\SQL\Expression\Keyword;
use NoreSources\SQL\Expression\TableReference;
use NoreSources\SQL\Expression\Value;

class InsertQuery extends Statement implements \ArrayAccess
{

	use ColumnValueTrait;

	/**
	 *
	 * @param TableStructure|string $table
	 * @param string $alias
	 *        	Optional table alias
	 */
	public function __construct($table, $alias = null)
	{
		if ($table instanceof TableStructure)
		{
			$table = $table->getPath();
		}

		$this->table = new TableReference($table);
		$this->columnValues = new \ArrayObject();
	}

	public function tokenize(TokenStream &$stream, BuildContext $context)
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
				->expression($this->table, $context);
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
				if ($column->hasColumnProperty(K::COLUMN_PROPERTY_DATA_TYPE))
					$type = $column->getColumnProperty(K::COLUMN_PROPERTY_DATA_TYPE);

				$value = new Value($value, $type);
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

				if ($column->hasColumnProperty(K::COLUMN_PROPERTY_DEFAULT_VALUE))
				{
					$c++;
					$columns[] = $context->escapeIdentifier($name);
					if ($builderFlags & K::BUILDER_INSERT_DEFAULT_KEYWORD)
					{
						$values[] = new Keyword(K::KEYWORD_DEFAULT);
					}
					else
					{
						$x = Evaluator::evaluate(
							$column->getColumnProperty(K::COLUMN_PROPERTY_DEFAULT_VALUE));
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
	 * @var TableReference
	 */
	private $table;
}
