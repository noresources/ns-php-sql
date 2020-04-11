<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\Expression;

use NoreSources\SQL\Constants as K;

/**
 * Shorthand expression for SQL BETWEEN operator.
 *
 * expression BETWEEN expression AND expression
 */
class Between implements TokenizableExpression, ExpressionReturnType
{

	/**
	 * Indicate if the left operand must be inside or ouside the range
	 *
	 * @var boolean
	 */
	public $inside;

	/**
	 *
	 * @param TokenizableExpression $leftOperand
	 * @param TokenizableExpression $min
	 * @param TokenizableExpression $max
	 */
	public function __construct(TokenizableExpression $leftOperand, TokenizableExpression $min,
		TokenizableExpression $max, $inside = true)
	{
		$this->inside = true;
		$this->leftOperand = $leftOperand;
		$this->range = [
			$min,
			$max
		];
	}

	public function getExpressionDataType()
	{
		return K::DATATYPE_BOOLEAN;
	}

	/**
	 *
	 * @return \NoreSources\SQL\TokenizableExpression\Expression
	 */
	public function getLeftOperand()
	{
		return $this->leftOperand;
	}

	/**
	 *
	 * @return array<integer,TokenizableExpression>
	 */
	public function getRange()
	{
		return $this->range;
	}

	/**
	 *
	 * @return TokenizableExpression Range min boundary
	 */
	public function getMin()
	{
		return $this->range[0];
	}

	/**
	 *
	 * @return TokenizableExpression Range max boundary
	 */
	public function getMax()
	{
		return $this->range[1];
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\TokenizableExpression::tokenize()
	 */
	public function tokenize(TokenStream $stream, TokenStreamContext $context)
	{
		$stream->expression($this->leftOperand, $context);
		if (!$this->inside)
			$stream->space()->text('NOT');

		return $stream->space()
			->text('BETWEEN')
			->space()
			->expression($this->range[0], $context)
			->space()
			->text('AND')
			->space()
			->expression($this->range[1], $context);
	}

	/**
	 *
	 * @var TokenizableExpression
	 */
	private $leftOperand;

	/**
	 *
	 * @var \array<integer,TokenizableExpression>
	 */
	private $range;
}