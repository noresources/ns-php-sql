<?php
namespace NoreSources\SQL\Syntax;

use NoreSources\Container;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Structure\CheckTableConstraint;
use NoreSources\SQL\Structure\ForeignKeyTableConstraint;
use NoreSources\SQL\Structure\IndexTableConstraintInterface;
use NoreSources\SQL\Structure\PrimaryKeyTableConstraint;
use NoreSources\SQL\Structure\StructureElementIdentifier;
use NoreSources\SQL\Structure\TableConstraint;
use NoreSources\SQL\Structure\TableConstraintInterface;
use NoreSources\SQL\Structure\TableStructure;

class TableConstraintDeclaration implements
	TokenizableExpressionInterface
{

	public function __construct(TableStructure $table = null,
		$constraint = null)
	{
		if (isset($table))
			$this->table($table);
		if (isset($constraint))
			$this->constraint($constraint);
	}

	public function table(TableStructure $table)
	{
		$this->table = $table;
		return $this;
	}

	public function getTable()
	{
		return $this->table;
	}

	/**
	 *
	 * @param mixed $constraint
	 * @return $this
	 */
	public function constraint(TableConstraintInterface $constraint)
	{
		$this->constraint = $constraint;
		return $this;
	}

	/**
	 *
	 * @return TableConstraintInterface
	 */
	public function getConstraint()
	{
		return $this->constraint;
	}

	public function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$platform = $context->getPlatform();

		if ($this->constraint->getName())
			$stream->keyword('constraint')
				->space()
				->identifier(
				$platform->quoteIdentifier($this->constraint->getName()));

		$c = $stream->count();

		if ($this->constraint instanceof IndexTableConstraintInterface)
			$this->tokenizeIndexTableConstraint($stream, $context);
		elseif ($this->constraint instanceof ForeignKeyTableConstraint)
			$this->tokenizeForeignKey($stream, $context);
		elseif ($this->constraint instanceof CheckTableConstraint)
			$this->tokenizeCheckTableConstraint($stream, $context);

		$c2 = $stream->count();
		if ($c && ($c != $c2))
			$stream->streamAt((new TokenStream())->space(), 1);

		return $stream;
	}

	public function tokenizeCheckTableConstraint(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		/**
		 *
		 * @var CheckTableConstraint $check
		 */
		$check = $this->constraint;
		$expression = $check->getConstraintExpression();

		if (!($expression && Container::count($expression)))
			$expression = new Data(true, K::DATATYPE_BOOLEAN);

		$stream->keyword('check')
			->space()
			->text('(')
			->constraints($expression, $context)
			->text(')');

		return $this;
	}

	/**
	 *
	 * @param TokenStream $stream
	 * @param TokenStreamContextInterface $context
	 * @return TableConstraint
	 */
	public function tokenizeIndexTableConstraint(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$stream->keyword($this->getIndexTableConstraintNameKeyword());

		$stream->space()->text('(');
		$i = 0;
		foreach ($this->constraint->getColumns() as $column)
		{
			$column = StructureElementIdentifier::make($column);
			if ($i++ > 0)
				$stream->text(',')->space();

			$stream->identifier(
				$context->getPlatform()
					->quoteIdentifier($column->getLocalName()));
		}
		$stream->text(')');
		return $this;
	}

	/**
	 *
	 * @param TokenStream $stream
	 * @param TokenStreamContextInterface $context
	 * @return TokenStream
	 */
	public function tokenizeForeignKey(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$stream->keyword('foreign key')
			->space()
			->text('(');

		$i = 0;
		foreach ($this->constraint as $column => $reference)
		{
			if ($i++ > 0)
				$stream->text(',')->space();

			$stream->identifier(
				$context->getPlatform()
					->quoteIdentifier($column));
		}
		$stream->text(')');

		$stream->space()
			->keyword('references')
			->space();

		$ft = $context->findTable(
			\strval($this->constraint->getForeignTable()));
		if ($ft->getParentElement() == $this->table->getParentElement())
			$stream->identifier(
				$context->getPlatform()
					->quoteIdentifier($ft->getName()));
		else
			$stream->identifier(
				$context->getPlatform()
					->quoteIdentifierPath(
					$this->constraint->getForeignTable()));

		$stream->space()->text('(');

		$i = 0;
		foreach ($this->constraint as $column => $reference)
		{
			if ($i++ > 0)
				$stream->text(',')->space();
			$stream->identifier(
				$context->getPlatform()
					->quoteIdentifier($reference));
		}
		$stream->text(')');

		if ($this->constraint->onUpdate)
		{
			$stream->space()
				->keyword('on update')
				->space()
				->keyword(
				$context->getPlatform()
					->getForeignKeyAction($this->constraint->onUpdate));
		}

		if ($this->constraint->onDelete)
		{
			$stream->space()
				->keyword('on delete')
				->space()
				->keyword(
				$context->getPlatform()
					->getForeignKeyAction($this->constraint->onDelete));
		}

		return $stream;
	}

	protected function getIndexTableConstraintNameKeyword()
	{
		if ($this->constraint instanceof PrimaryKeyTableConstraint)
			return 'primary key';
		return 'unique';
	}

	/**
	 *
	 * @var TableStructure
	 */
	private $table;

	/**
	 *
	 * @var TableConstraint
	 */
	private $constraint;
}
