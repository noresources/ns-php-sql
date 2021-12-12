<?php
/**
 * Copyright © 2020 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax;

use NoreSources\Container\Container;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Structure\CheckTableConstraint;
use NoreSources\SQL\Structure\ForeignKeyTableConstraint;
use NoreSources\SQL\Structure\Identifier;
use NoreSources\SQL\Structure\KeyTableConstraintInterface;
use NoreSources\SQL\Structure\PrimaryKeyTableConstraint;
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

		if ($this->constraint instanceof KeyTableConstraintInterface)
			$this->tokenizeKeyTableConstraint($stream, $context);
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
	public function tokenizeKeyTableConstraint(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$stream->keyword($this->getKeyTableConstraintNameKeyword());

		$stream->space()->text('(');
		$i = 0;
		foreach ($this->constraint->getColumns() as $column)
		{
			$column = Identifier::make($column);
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
		$platform = $context->getPlatform();

		$constraintFeatureFlags = $platform->queryFeature(
			[
				K::FEATURE_CREATE,
				K::FEATURE_CONSTRAINT_DECLARATION_FLAGS
			], 0);

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
		if (($ft->getParentElement() != $this->table->getParentElement()) ||
			($constraintFeatureFlags &
			K::FEATURE_CONSTRAINT_REFERENCES_QUALIFIED))
			$stream->identifier(
				$context->getPlatform()
					->quoteIdentifierPath(
					$this->constraint->getForeignTable()));
		else
			$stream->identifier(
				$context->getPlatform()
					->quoteIdentifier($ft->getName()));

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

		if ($this->constraint->getEvents()->has(K::EVENT_UPDATE))
		{
			$stream->space()
				->keyword('on update')
				->space()
				->keyword(
				$context->getPlatform()
					->getForeignKeyAction(
					$this->constraint->getEvents()
						->get(K::EVENT_UPDATE)));
		}

		if ($this->constraint->getEvents()->has(K::EVENT_DELETE))
		{
			$stream->space()
				->keyword('on delete')
				->space()
				->keyword(
				$context->getPlatform()
					->getForeignKeyAction(
					$this->constraint->getEvents()
						->get(K::EVENT_DELETE)));
		}

		return $stream;
	}

	protected function getKeyTableConstraintNameKeyword()
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
