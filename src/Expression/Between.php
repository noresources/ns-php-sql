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
use NoreSources\SQL\DataTypeProviderInterface;

/**
 * Shorthand expression for SQL BETWEEN operator.
 *
 * expression BETWEEN expression AND expression
 */
class Between implements TokenizableExpressionInterface,
	DataTypeProviderInterface
{

	/**
	 * Indicate if the left operand must be inside or ouside the range
	 *
	 * @var boolean
	 */
	public $inside;

	/**
	 *
	 * @param TokenizableExpressionInterface $leftOperand
	 * @param TokenizableExpressionInterface $min
	 * @param TokenizableExpressionInterface $max
	 */
	public function __construct(
		TokenizableExpressionInterface $leftOperand,
		TokenizableExpressionInterface $min,
		TokenizableExpressionInterface $max, $inside = true)
	{
		$this->inside = true;
		$this->leftOperand = $leftOperand;
		$this->range = [
			$min,
			$max
		];
	}

	public function getDataType()
	{
		return K::DATATYPE_BOOLEAN;
	}

	/**
	 *
	 * @return \NoreSources\SQL\Expression\TokenizableExpressionInterface
	 */
	public function getLeftOperand()
	{
		return $this->leftOperand;
	}

	/**
	 *
	 * @return array<integer,TokenizableExpressionInterface>
	 */
	public function getRange()
	{
		return $this->range;
	}

	/**
	 *
	 * @return TokenizableExpressionInterface Range min boundary
	 */
	public function getMin()
	{
		return $this->range[0];
	}

	/**
	 *
	 * @return TokenizableExpressionInterface Range max boundary
	 */
	public function getMax()
	{
		return $this->range[1];
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\TokenizableExpressionInterface::tokenize()
	 */
	public function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
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
	 * @var TokenizableExpressionInterface
	 */
	private $leftOperand;

	/**
	 *
	 * @var \array<integer,TokenizableExpressionInterface>
	 */
	private $range;
}