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
 * Sequence of SQL language tokens
 */
class TokenStream implements \IteratorAggregate, \Countable
{

	const INDEX_TOKEN = 0;

	const INDEX_TYPE = 1;

	public function __construct()
	{
		$this->tokens = new \ArrayObject();
	}

	/**
	 * Add a space to stream
	 *
	 * @return \NoreSources\SQL\Expression\TokenStream
	 */
	public function space()
	{
		return $this->append(' ', K::TOKEN_SPACE);
	}

	/**
	 * Add a literal to stream
	 *
	 * @param mixed $value
	 *        	Literal value
	 * @return \NoreSources\SQL\Expression\TokenStream
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
	 * @return \NoreSources\SQL\Expression\TokenStream
	 */
	public function identifier($value)
	{
		return $this->append($value, K::TOKEN_IDENTIFIER);
	}

	/**
	 * Add SQL syntax keyword to stream
	 *
	 * @param string $value
	 *        	Keyword
	 * @return \NoreSources\SQL\Expression\TokenStream
	 */
	public function keyword($value)
	{
		return $this->append(strtoupper(trim($value)), K::TOKEN_KEYWORD);
	}

	/**
	 * Add arbitrary text to the stream
	 *
	 * @param string $value
	 * @return \NoreSources\SQL\Expression\TokenStream
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
	 * @return \NoreSources\SQL\Expression\TokenStream
	 */
	public function parameter($value)
	{
		return $this->append($value, K::TOKEN_PARAMETER);
	}

	/**
	 * Add an expression to the stream
	 *
	 * @param TokenizableExpressionInterface $expression
	 * @param TokenStreamContextInterface $context
	 * @return \NoreSources\SQL\Expression\TokenStream
	 */
	public function expression(TokenizableExpressionInterface $expression, TokenStreamContextInterface $context)
	{
		$expression->tokenize($this, $context);
		return $this;
	}

	/**
	 * Add TableConstraint expression to stream
	 *
	 * @param array|\Traversable $constraints
	 * @param TokenStreamContextInterface $context
	 * @return \NoreSources\SQL\Expression\TokenStream
	 */
	public function constraints($constraints, TokenStreamContextInterface $context)
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

		if ($c instanceof TokenizableExpressionInterface)
			return $this->expression($c, $context);

		return $this;
	}

	/**
	 * Append arbitrary token to stream
	 *
	 * @param mixed $token
	 *        	Token value
	 * @param integer $type
	 *        	Token type
	 * @return \NoreSources\SQL\Expression\TokenStream
	 */
	public function append($token, $type)
	{
		$this->tokens->append(array(
			self::INDEX_TOKEN => $token,
			self::INDEX_TYPE => $type
		));
		return $this;
	}

	/**
	 * Merge a TokenStream to the stream
	 *
	 * @param TokenStream $stream
	 * @return \NoreSources\SQL\Expression\TokenStream
	 */
	public function stream(TokenStream $stream)
	{
		foreach ($stream as $token)
		{
			$this->tokens->append($token);
		}
		return $this;
	}

	/**
	 *
	 * @return Number of token in the stream
	 */
	public function count()
	{
		return $this->tokens->count();
	}

	public function getIterator()
	{
		return $this->tokens->getIterator();
	}

	/**
	 *
	 * @var \ArrayObject Token stream
	 */
	private $tokens;
}