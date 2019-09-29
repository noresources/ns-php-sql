<?php

// Namespace
namespace NoreSources\SQL;

// Aliases
use NoreSources as ns;
use NoreSources\ArrayUtil;
use NoreSources\SQL\Constants as K;

class CreateTableQuery extends Statement
{

	/**
	 *
	 * @param TableStructure $structure
	 *        	Table structire to create
	 */
	public function __construct(TableStructure $structure = null)
	{
		$this->structure = $structure;
	}

	/**
	 *
	 * @property-read \NoreSources\SQL\TableStructure
	 * @param mixed $member
	 * @return \NoreSources\SQL\TableStructure|unknown
	 */
	public function __get($member)
	{
		if ($member == 'structure')
			return $this->structure;

		return $this->structure->$member;
	}

	public function tokenize(TokenStream &$stream, StatementContext $context)
	{
		$builderFlags = $context->getBuilderFlags(K::BUILDER_DOMAIN_GENERIC);
		$builderFlags |= $context->getBuilderFlags(K::BUILDER_DOMAIN_CREATE_TABLE);

		$structure = $this->structure;
		if (!($structure instanceof TableStructure))
		{
			$structure = $context->getPivot();
		}

		if (!($structure instanceof TableStructure && ($structure->count() > 0)))
		{
			throw new StatementException($this, 'Missing or invalid table structure');
		}

		/**
		 *
		 * @todo IF NOT EXISTS (if available)
		 */

		$stream->keyword('create')
			->space()
			->keyword('table');

		if ($builderFlags & K::BUILDER_IF_NOT_EXISTS)
		{
			$stream->space()
				->keyword('if')
				->space()
				->keyword('not')
				->space()
				->keyword('exists');
		}

		$stream->space()
			->identifier($context->getCanonicalName($this->structure))
			->space()
			->text('(');

		// Columns

		$c = 0;
		foreach ($this->structure as $name => $column)
		{
			/**
			 *
			 * @var TableColumnStructure $column
			 */

			if ($c++ > 0)
				$stream->text(',')->space();

			$stream->identifier($context->escapeIdentifier($column->getName()))
				->space()
				->identifier($context->getColumnTypeName($column));

			if ($column->hasProperty(K::COLUMN_PROPERTY_DATA_SIZE))
			{
				/**
				 *
				 * @todo only if supported
				 */
				$stream->text('(')
					->literal($column->getProperty(K::COLUMN_PROPERTY_DATA_SIZE))
					->text(')');
			}

			if (!$column->getProperty(K::COLUMN_PROPERTY_NULL))
			{
				$stream->space()
					->keyword('NOT')
					->space()
					->keyword('NULL');
			}

			if ($column->hasProperty(K::COLUMN_PROPERTY_DEFAULT_VALUE))
			{
				$v = $context->evaluateExpression(
					$column->getProperty(K::COLUMN_PROPERTY_DEFAULT_VALUE));
				$stream->space()
					->keyword('DEFAULT')
					->space()
					->expression($v, $context);
			}

			if ($column->hasProperty(K::COLUMN_PROPERTY_AUTOINCREMENT) &&
				$column->getProperty(K::COLUMN_PROPERTY_AUTOINCREMENT))
			{
				$stream->space()->keyword($context->getKeyword(K::KEYWORD_AUTOINCREMENT));
			}
		}

		// Constraints
		foreach ($structure->constraints as $constraint)
		{
			/**
			 *
			 * @var TableConstraint $constraint
			 */

			if ($c++ > 0)
				$stream->text(',')->space();

			if (strlen($constraint->constraintName))
			{
				$stream->keyword('constraint')
					->space()
					->identifier($context->escapeIdentifier($constraint->constraintName));
			}

			if ($constraint instanceof ColumnTableConstraint)
			{
				if ($constraint instanceof PrimaryKeyTableConstraint)
					$stream->keyword('primary key');
				elseif ($constraint instanceof UniqueTableConstraint)
					$stream->keyword('unique');

				$stream->space()->text('(');
				$i = 0;
				foreach ($constraint as $column)
				{
					if ($i++ > 0)
						$stream->text(',')->space();

					$stream->identifier($context->escapeIdentifier($column->getName()));
				}
				$stream->text(')');
			}
			elseif ($constraint instanceof ForeignKeyTableConstraint)
			{
				$stream->keyword('foreign key')
					->space()
					->text('(');

				$i = 0;
				foreach ($constraint as $column => $reference)
				{
					if ($i++ > 0)
						$stream->text(',')->space();

					$stream->identifier($context->escapeIdentifier($column));
				}
				$stream->text(')');

				$stream->space()
					->keyword('references')
					->space()
					->identifier($context->getCanonicalName($constraint->foreignTable))
					->space()
					->text('(');

				$i = 0;
				foreach ($constraint as $column => $reference)
				{
					if ($i++ > 0)
						$stream->text(',')->space();
					$stream->identifier($context->escapeIdentifier($reference));
				}
				$stream->text(')');

				if ($constraint->onUpdate)
				{
					$stream->space()
						->keyword('on update')
						->space()
						->keyword($context->getForeignKeyAction($constraint->onUpdate));
				}

				if ($constraint->onDelete)
				{
					$stream->space()
						->keyword('on delete')
						->space()
						->keyword($context->getForeignKeyAction($constraint->onDelete));
				}
			}
		} // constraints

		return $stream->text(')');
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\Expression::traverse()
	 */
	public function traverse($callable, StatementContext $context, $flags = 0)
	{
		call_user_func($callable, $this, $context, $flags);
	}

	/**
	 *
	 * @var TableStructure
	 */
	private $structure;
}