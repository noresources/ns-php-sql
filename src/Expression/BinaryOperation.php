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
 * Binary operator expression
 */
class BinaryOperation extends xpr\BinaryOperation implements TokenizableExpressionInterface,
	ExpressionReturnTypeInterface
{

	const EQUAL = '=';

	const DIFFER = '<>';

	/**
	 *
	 * @param string $operator
	 *        	Operator text
	 * @param TokenizableExpressionInterface $left
	 * @param TokenizableExpressionInterface $right
	 */
	public function __construct($operator, TokenizableExpressionInterface $left, TokenizableExpressionInterface $right)
	{
		parent::__construct($operator, $left, $right);
	}

	public function isComparison()
	{
		return (parent::isComparison() ||
			\in_array($this->getOperator(), [
				self::EQUAL,
				self::DIFFER
			]));
	}

	public function getExpressionDataType()
	{
		if ($this->isComparison())
			return K::DATATYPE_BOOLEAN;

		$type = K::DATATYPE_UNDEFINED;
		if ($this->isArithmetic())
		{
			$type = ExpressionHelper::getExpressionDataType($this->getLeftOperand());
			if ($type == K::DATATYPE_UNDEFINED)
				return ExpressionHelper::getExpressionDataType($this->getRightOperand());
		}

		return $type;
	}

	public function tokenize(TokenStream $stream, TokenStreamContextInterface $context)
	{
		return $stream->expression($this->getLeftOperand(), $context)
			->space()
			->text($this->getOperator())
			->space()
			->expression($this->getRightOperand(), $context);
	}
}