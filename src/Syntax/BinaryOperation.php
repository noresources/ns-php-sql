<?php
/**
 * Copyright © 2012 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax;

use NoreSources\Expression as xpr;
use NoreSources\Expression\ExpressionInterface;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DataDescription;
use NoreSources\SQL\DataTypeProviderInterface;

/**
 * Binary operator expression
 */
class BinaryOperation extends xpr\BinaryOperation implements
	TokenizableExpressionInterface, DataTypeProviderInterface
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
	public function __construct($operator, ExpressionInterface $left,
		ExpressionInterface $right)
	{
		parent::__construct($operator, $left, $right);
	}

	public function getDataType()
	{
		if ($this->isComparison())
			return K::DATATYPE_BOOLEAN;

		$type = K::DATATYPE_UNDEFINED;
		if ($this->isArithmetic())
		{
			$type = DataDescription::getInstance()->getDataType(
				$this->getLeftOperand());
			if ($type == K::DATATYPE_UNDEFINED)
				return DataDescription::getInstance()->getDataType(
					$this->getRightOperand());
		}

		return $type;
	}

	public function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		return Tokenizer::getInstance()->tokenizeBinaryOperation($this,
			$stream, $context);
	}
}