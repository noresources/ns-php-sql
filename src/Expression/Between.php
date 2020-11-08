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

use NoreSources\SQL\DataTypeProviderInterface;
use NoreSources\SQL\Expression\Traits\ToggleableTrait;

/**
 * Shorthand expression for SQL BETWEEN operator.
 *
 * expression BETWEEN expression AND expression
 */
class Between implements TokenizableExpressionInterface,
	DataTypeProviderInterface, ToggleableInterface
{

	use ToggleableTrait;

	/**
	 *
	 * @param Evaluable $leftOperand
	 * @param Evaluable $min
	 * @param Evaluable $max
	 */
	public static function createWithParameterList($leftOperand, $min,
		$max)
	{
		return new Between(Evaluator::evaluate($leftOperand),
			Evaluator::evaluate($min), Evaluator::evaluate($max));
	}

	/**
	 *
	 * @param TokenizableExpressionInterface $leftOperand
	 * @param TokenizableExpressionInterface $min
	 * @param TokenizableExpressionInterface $max
	 */
	public function __construct(
		TokenizableExpressionInterface $leftOperand,
		TokenizableExpressionInterface $min,
		TokenizableExpressionInterface $max)

	{
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
		if (!$this->getToggleState())
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