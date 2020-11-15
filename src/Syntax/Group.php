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

use NoreSources\Container;
use NoreSources\Expression\ExpressionInterface;
use NoreSources\SQL\DataTypeProviderInterface;

class Group implements TokenizableExpressionInterface,
	DataTypeProviderInterface
{

	public static function createWithParameterList(
		ExpressionInterface $leftOperand, ...$operands)
	{
		if (Container::count($operands) != 1)
			throw \LogicException(
				'Only 1 operand expected for ' . static::class . '. Got ' .
				Container::count($operands));
		return new Group(Container::firstValue($operands));
	}

	/**
	 *
	 * @param ExpressionInterface $expression
	 * @param string $open
	 * @param string $close
	 */
	public function __construct(ExpressionInterface $expression,
		$open = '(', $close = ')')
	{
		$this->expression = $expression;
		$this->openingText = $open;
		$this->closingText = $close;
	}

	/**
	 *
	 * @return ExpressionInterface
	 */
	public function getExpression()
	{
		return $this->expression;
	}

	/**
	 *
	 * @return string
	 */
	public function getOpeningText()
	{
		return $this->openingText;
	}

	/**
	 *
	 * @return string
	 */
	public function getClosingText()
	{
		return $this->getClosingText();
	}

	public function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		return $stream->text($this->openingText)
			->expression($this->expression, $context)
			->text($this->closingText);
	}

	public function getDataType()
	{
		return Evaluator::getInstance()->getDataType($this->expression);
	}

	/**
	 *
	 * @var ExpressionInterface
	 */
	private $expression;

	/**
	 *
	 * @var string
	 */
	private $openingText;

	/**
	 *
	 * @var string
	 */
	private $closingText;
}