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

use NoreSources\Expression as xpr;
use NoreSources\SQL\Constants as K;

/**
 * Unary operator expression
 */
class UnaryOperation extends xpr\UnaryOperation implements TokenizableExpressionInterface,
	ExpressionReturnTypeInterface
{

	public function __construct($operator, TokenizableExpressionInterface $operand)
	{
		parent::__construct($operator, $operand);
	}

	public function tokenize(TokenStream $stream, TokenStreamContextInterface $context)
	{
		$stream->text($this->getOperator());
		if (\preg_match('/[a-z][0-9]/i', $this->getOperator()))
			$stream->space();

		return $stream->expression($this->getOperand(), $context);
	}

	public function getExpressionDataType()
	{
		switch ($this->getOperator())
		{
			case self::MINUS:
				return ExpressionHelper::getExpressionDataType($this->getOperand());
			case self::BITWISE_NOT:
				return K::DATATYPE_INTEGER;
			case self::LOGICAL_NOT:
				return K::DATATYPE_BOOLEAN;
		}

		return K::DATATYPE_UNDEFINED;
	}
}