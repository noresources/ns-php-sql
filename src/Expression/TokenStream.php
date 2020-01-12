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
use NoreSources\SQL\Statement\BuildContext;

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

	public function space()
	{
		return $this->append(' ', K::TOKEN_SPACE);
	}

	public function literal($value)
	{
		return $this->append($value, K::TOKEN_LITERAL);
	}

	public function identifier($value)
	{
		return $this->append($value, K::TOKEN_IDENTIFIER);
	}

	public function keyword($value)
	{
		return $this->append(strtoupper(trim($value)), K::TOKEN_KEYWORD);
	}

	public function text($value)
	{
		return $this->append($value, K::TOKEN_TEXT);
	}

	public function parameter($value)
	{
		return $this->append($value, K::TOKEN_PARAMETER);
	}

	public function expression(Expression $expression, BuildContext $context)
	{
		$expression->tokenize($this, $context);
		return $this;
	}

	/**
	 *
	 * @param array|\Traversable $constraints
	 * @param BuildContext $context
	 * @return \NoreSources\SQL\Expression\TokenStream
	 */
	public function constraints($constraints, BuildContext $context)
	{
		$c = null;
		foreach ($constraints as $constraint)
		{
			$e = Evaluator::evaluate($constraint);
			if ($c instanceof Expression)
				$c = new BinaryOperation('AND', $c, $e);
			else
				$c = $e;
		}

		if ($c instanceof Expression)
			return $this->expression($c, $context);

		return $this;
	}

	public function append($token, $type)
	{
		$this->tokens->append(array(
			self::INDEX_TOKEN => $token,
			self::INDEX_TYPE => $type
		));
		return $this;
	}

	public function stream(TokenStream $stream)
	{
		foreach ($stream as $token)
		{
			$this->tokens->append($token);
		}
		return $this;
	}

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