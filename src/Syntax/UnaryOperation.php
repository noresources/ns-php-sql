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

use NoreSources\Expression as xpr;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DataTypeProviderInterface;

/**
 * Unary operator expression
 */
class UnaryOperation extends xpr\UnaryOperation implements
	TokenizableExpressionInterface, DataTypeProviderInterface
{

	public function __construct($operator,
		TokenizableExpressionInterface $operand)
	{
		parent::__construct($operator, $operand);
	}

	public function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		return Tokenizer::getInstance()->tokenizeUnaryOperation($this,
			$stream, $context);
	}

	public function getDataType()
	{
		switch ($this->getOperator())
		{
			case self::MINUS:
				return Evaluator::getInstance()->getDataType(
					$this->getOperand());
			case self::BITWISE_NOT:
				return K::DATATYPE_INTEGER;
			case self::LOGICAL_NOT:
				return K::DATATYPE_BOOLEAN;
		}

		return K::DATATYPE_UNDEFINED;
	}
}