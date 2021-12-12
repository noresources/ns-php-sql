<?php
/**
 * Copyright © 2020 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax;

use NoreSources\SQL\DataTypeProviderInterface;

/**
 * CASE expression
 */
class Alternative implements TokenizableExpressionInterface,
	DataTypeProviderInterface
{

	public function __construct(TokenizableExpressionInterface $when,
		TokenizableExpressionInterface $then)
	{
		$this->when = $when;
		$this->then = $then;
	}

	public function getDataType()
	{
		return Evaluator::getInstance()->getDataType($this->then);
	}

	public function getCondition()
	{
		return $this->when;
	}

	public function getValue()
	{
		return $this->then;
	}

	public function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		return $stream->keyword('when')
			->space()
			->expression($this->when, $context)
			->space()
			->keyword('then')
			->space()
			->expression($this->then, $context);
	}

	/**
	 *
	 * @var TokenizableExpressionInterface
	 */
	private $when;

	/**
	 *
	 * @var TokenizableExpressionInterface
	 */
	private $then;
}

