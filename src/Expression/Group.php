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

class Group implements TokenizableExpressionInterface, ExpressionReturnTypeInterface
{

	/**
	 *
	 * @param TokenizableExpressionInterface $expression
	 * @param string $open
	 * @param string $close
	 */
	public function __construct(TokenizableExpressionInterface $expression, $open = '(', $close = ')')
	{
		$this->expression = $expression;
		$this->openingText = $open;
		$this->closingText = $close;
	}

	/**
	 *
	 * @return \NoreSources\SQL\Expression\TokenizableExpressionInterface
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

	public function tokenize(TokenStream $stream, TokenStreamContextInterface $context)
	{
		return $stream->text($this->openingText)
			->expression($this->expression, $context)
			->text($this->closingText);
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\Expression\ExpressionReturnTypeInterface::getExpressionDataType()
	 */
	public function getExpressionDataType()
	{
		return ExpressionHelper::getExpressionDataType($this->expression);
	}

	/**
	 *
	 * @var TokenizableExpressionInterface
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