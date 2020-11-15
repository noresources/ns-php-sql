<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax;

use NoreSources\Expression\ExpressionInterface;
use NoreSources\SQL\DataTypeProviderInterface;
use NoreSources\SQL\Syntax\Traits\ToggleableTrait;

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
	 * @param ExpressionInterface $leftOperand
	 * @param ExpressionInterface $min
	 * @param ExpressionInterface $max
	 */
	public function __construct(ExpressionInterface $leftOperand,
		ExpressionInterface $min, ExpressionInterface $max)

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
	 * @return ExpressionInterface
	 */
	public function getLeftOperand()
	{
		return $this->leftOperand;
	}

	/**
	 *
	 * @return ExpressionInterface
	 */
	public function getRange()
	{
		return $this->range;
	}

	/**
	 *
	 * @return ExpressionInterface Range min boundary
	 */
	public function getMin()
	{
		return $this->range[0];
	}

	/**
	 *
	 * @return ExpressionInterface Range max boundary
	 */
	public function getMax()
	{
		return $this->range[1];
	}

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
	 * @var ExpressionInterface
	 */
	private $leftOperand;

	/**
	 *
	 * @var ExpressionInterface[]
	 */
	private $range;
}