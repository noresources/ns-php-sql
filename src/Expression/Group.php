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

class Group implements TokenizableExpression, ExpressionReturnType
{

	/**
	 *
	 * @param TokenizableExpression $expression
	 * @param string $open
	 * @param string $close
	 */
	public function __construct(TokenizableExpression $expression, $open = '(', $close = ')')
	{
		$this->expression = $expression;
		$this->openingText = $open;
		$this->closingText = $close;
	}

	/**
	 *
	 * @return \NoreSources\TokenizableExpression\Expression
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
	 * @return unknown
	 */
	public function getClosingText()
	{
		return $this->getClosingText();
	}

	public function tokenize(TokenStream $stream, TokenStreamContext $context)
	{
		return $stream->text($this->openingText)
			->expression($this->expression, $context)
			->text($this->closingText);
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\TokenizableExpression\ExpressionReturnType::getExpressionDataType()
	 */
	public function getExpressionDataType()
	{
		return Helper::getExpressionDataType($this->expression);
	}

	/**
	 *
	 * @var TokenizableExpression
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