<?php
namespace NoreSources\SQL\Expression;

use NoreSources\SQL as sql;
use NoreSources\SQL\Constants as K;
use NoreSources\Expression as xpr;

class UnaryOperation extends xpr\UnaryOperation implements Expression, ExpressionReturnType
{

	public function __construct($operator, Expression $operand)
	{
		parent::__construct($operator, $operand);
	}

	public function tokenize(sql\TokenStream &$stream, sql\BuildContext $context)
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
				return Helper::getExpressionDataType($this->getOperand());
			case self::BITWISE_NOT:
				return K::DATATYPE_INTEGER;
			case self::LOGICAL_NOT:
				return K::DATATYPE_BOOLEAN;
		}

		return K::DATATYPE_UNDEFINED;
	}
}