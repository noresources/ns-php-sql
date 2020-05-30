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
namespace NoreSources\SQL\Expression;

use NoreSources\TypeConversion;
use NoreSources\TypeDescription;
use NoreSources\SQL\Statement\StatementTokenStreamContext;
use NoreSources\SQL\Statement\Query\SelectQuery;
use NoreSources\SQL\Structure\TableStructure;
use NoreSources\SQL\Structure\ViewStructure;

class DataRowContainerReference implements TokenizableExpressionInterface
{

	/**
	 *
	 * @var string
	 */
	public $alias;

	/**
	 *
	 * @var Table|SelectQuery
	 */
	public $expression;

	public function __construct($reference, $alias = null)
	{
		if ($reference instanceof TableStructure || $reference instanceof ViewStructure)
			$reference = new Table($reference->getPath());
		elseif ($reference instanceof SelectQuery)
		{
			if ($alias == null)
				throw new \LogicException(
					"Alias is mandatory for " . TypeDescription::getName($reference));
		}
		elseif (TypeDescription::hasStringRepresentation($reference))
			$reference = new Table(TypeConversion::toString($reference));
		elseif (!($reference instanceof Table))
			throw new \InvalidArgumentException(
				Table::class . ', ' . SelectQuery::class . ' or string expected. Got ' .
				TypeDescription::getName($reference));

		$this->expression = $reference;
		$this->alias = $alias;
	}

	public function tokenize(TokenStream $stream, TokenStreamContextInterface $context)
	{
		$ctx = $context;
		if ($this->expression instanceof SelectQuery)
		{
			$stream->text('(');
			$ctx = new StatementTokenStreamContext($context->getStatementBuilder());
			if ($context->getPivot())
				$ctx->setPivot($context->getPivot());
		}

		$stream->expression($this->expression, $ctx);

		if ($this->expression instanceof SelectQuery)
			$stream->text(')');

		if ($this->alias)
			$stream->space()
				->keyword('as')
				->space()
				->identifier($ctx->getStatementBuilder()
				->escapeIdentifier($this->alias));

		if ($this->expression instanceof SelectQuery)
		{
			$data = $ctx->getStatementBuilder()->finalizeStatement($stream, $ctx);
			$context->setTemporaryTable($this->alias, $data->getResultColumns());
		}

		return $stream;
	}
}