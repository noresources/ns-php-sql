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

use NoreSources\ArrayRepresentation;
use NoreSources\Expression\ExpressionInterface;
use NoreSources\SQL\Constants as K;

/**
 * Sequence of SQL language tokens
 */
class TokenStream implements \IteratorAggregate, \Countable,
	ArrayRepresentation, \JsonSerializable
{

	const COMMENT = K::TOKEN_COMMENT;

	const IDENTIFIER = K::TOKEN_IDENTIFIER;

	const KEYWORD = K::TOKEN_KEYWORD;

	const LITERAL = K::TOKEN_LITERAL;

	const PARAMETER = K::TOKEN_PARAMETER;

	const SPACE = K::TOKEN_SPACE;

	const TEXT = K::TOKEN_TEXT;

	const INDEX_TOKEN = 0;

	const INDEX_TYPE = 1;

	public function __construct()
	{
		$this->tokens = new \ArrayObject();
	}

	/**
	 * Add a space to stream
	 *
	 * @return $this
	 */
	public function space()
	{
		$this->tokens->append([
			self::INDEX_TYPE => self::SPACE
		]);
		return $this;
	}

	/**
	 * Add a literal to stream
	 *
	 * @param mixed $value
	 *        	value
	 * @return $this
	 */
	public function literal($value)
	{
		return $this->append($value, K::TOKEN_LITERAL);
	}

	/**
	 * Add a structure identifier to the stream
	 *
	 * @param string $value
	 *        	Structure element identifier, type name etc.
	 * @return $this
	 */
	public function identifier($value)
	{
		return $this->append($value, K::TOKEN_IDENTIFIER);
	}

	/**
	 * Add SQL syntax keyword to stream
	 *
	 * @param string|integer $value
	 *        	Keyword identifier or keyword value
	 * @return $this
	 */
	public function keyword($value)
	{
		if (\is_string($value))
			$value = \strtoupper(\trim($value));
		return $this->append($value, K::TOKEN_KEYWORD);
	}

	/**
	 * Add arbitrary text to the stream
	 *
	 * @param string $value
	 * @return $this
	 */
	public function text($value)
	{
		return $this->append($value, K::TOKEN_TEXT);
	}

	/**
	 * Add Logical parameter key to stream
	 *
	 * @param string $value
	 *        	parameter key
	 * @return $this
	 */
	public function parameter($value)
	{
		return $this->append($value, K::TOKEN_PARAMETER);
	}

	/**
	 *
	 * @param string $value
	 * @return $this
	 */
	public function comment($value)
	{
		return $this->append($value, self::COMMENT);
	}

	/**
	 * Add an expression to the stream
	 *
	 * @param Evaluable $expression
	 * @param TokenStreamContextInterface $context
	 * @return $this
	 */
	public function expression($expression,
		TokenStreamContextInterface $context)
	{
		if (!($expression instanceof ExpressionInterface))
			$expression = Evaluator::evaluate($expression);

		if ($expression instanceof TokenizableExpressionInterface)
			return $expression->tokenize($this, $context);

		return Tokenizer::getInstance()->tokenizeExpression($expression,
			$this, $context);
	}

	/**
	 * Add TableConstraint expression to stream
	 *
	 * @param array|\Traversable $constraints
	 * @param TokenStreamContextInterface $context
	 * @return $this
	 */
	public function constraints($constraints,
		TokenStreamContextInterface $context)
	{
		$c = null;
		foreach ($constraints as $constraint)
		{
			$e = Evaluator::evaluate($constraint);
			if ($c instanceof TokenizableExpressionInterface)
				$c = new BinaryOperation('AND', $c, $e);
			else
				$c = $e;
		}

		if ($c instanceof ExpressionInterface)
			return $this->expression($c, $context);

		return $this;
	}

	/**
	 * Merge a TokenStream to the stream
	 *
	 * @param TokenStream $stream
	 * @return $this
	 */
	public function stream(TokenStream $stream)
	{
		foreach ($stream as $token)
		{
			$this->tokens->append($token);
		}
		return $this;
	}

	public function streamAt(TokenStream $stream, $at)
	{
		$newTokens = $stream->getArrayCopy();
		$tokens = $this->getArrayCopy();

		\array_splice($tokens, $at, 0, $newTokens);
		$this->tokens->exchangeArray($tokens);
		return $this;
	}

	/**
	 *
	 * @return int Number of token in the stream
	 */
	public function count()
	{
		return $this->tokens->count();
	}

	/**
	 *
	 * @return array
	 */
	public function jsonSerialize()
	{
		return $this->tokens->getArrayCopy();
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \NoreSources\ArrayRepresentation::getArrayCopy()
	 */
	public function getArrayCopy()
	{
		return $this->tokens->getArrayCopy();
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see IteratorAggregate::getIterator()
	 */
	public function getIterator()
	{
		return $this->tokens->getIterator();
	}

	/**
	 * Append arbitrary token to stream
	 *
	 * @param mixed $token
	 *        	Token value
	 * @param integer $type
	 *        	Token type
	 * @return \NoreSources\SQL\Syntax\TokenStream
	 */
	protected function append($token, $type)
	{
		$this->tokens->append(
			[
				self::INDEX_TOKEN => $token,
				self::INDEX_TYPE => $type
			]);
		return $this;
	}

	/**
	 *
	 * @var \ArrayObject Token stream
	 */
	private $tokens;
}