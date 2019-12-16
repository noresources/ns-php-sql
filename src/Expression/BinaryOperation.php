<?php
namespace NoreSources\SQL\Expression;

use NoreSources\SQL as sql;
use NoreSources\Expression as xpr;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Statement\BuildContext;

class BinaryOperation extends xpr\BinaryOperation implements Expression, ExpressionReturnType
{
	use xpr\BasicExpressionVisitTrait;

	const EQUAL = '=';

	const DIFFER = '<>';

	/**
	 *
	 * @param unknown $operator
	 * @param Expression $left
	 * @param Expression $right
	 */
	public function __construct($operator, Expression $left, Expression $right)
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
			$type = Helper::getExpressionDataType($this->getLeftOperand());
			if ($type == K::DATATYPE_UNDEFINED)
				return Helper::getExpressionDataType($this->getRightOperand());
		}

		return $type;
	}

	public function tokenize(TokenStream &$stream, BuildContext $context)
	{
		return $stream->expression($this->getLeftOperand(), $context)
			->space()
			->text($this->getOperator())
			->space()
			->expression($this->getRightOperand(), $context);
	}
}