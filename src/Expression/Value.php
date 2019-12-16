<?php
namespace NoreSources\SQL\Expression;

use NoreSources\SQL as sql;
use NoreSources\SQL\Constants as K;
use NoreSources\Expression as xpr;
use NoreSources\SQL\Statement\BuildContext;

class Value extends xpr\Value implements Expression, ExpressionReturnType
{

	public function __construct($value, $type = K::DATATYPE_STRING)
	{
		parent::__construct($value);
		$this->setSerializationTarget($type);
		if ($value instanceof xpr\Expression)
			throw new \LogicException('Value is already an Expression');
	}

	/**
	 * Set to which type the value should be serialized
	 *
	 * @param sql\ArrayColumnPropertyMap|integer $type
	 * @throws \InvalidArgumentException
	 */
	public function setSerializationTarget($type)
	{
		if ($type instanceof sql\ArrayColumnPropertyMap)
			$this->serializationTarget = $type;
		elseif (\is_integer($type))
			$this->serializationTarget = new sql\ArrayColumnPropertyMap(
				[
					K::COLUMN_PROPERTY_DATA_TYPE => $type
				]);
		else
			throw new \InvalidArgumentException(
				ns\TypeDescription::getName($type) . 'is not a valid target argument for ' .
				ns\TypeDescription::getName($this));
	}

	public function getExpressionDataType()
	{
		if ($this->serializationTarget instanceof sql\ArrayColumnPropertyMap)
		{
			if ($this->serializationTarget->hasColumnProperty(K::COLUMN_PROPERTY_DATA_TYPE))
				return $this->serializationTarget->getColumnProperty(K::COLUMN_PROPERTY_DATA_TYPE);
		}

		return Helper::getExpressionDataType($this->getValue());
	}

	public function tokenize(TokenStream &$stream, BuildContext $context)
	{
		return $stream->literal(
			$context->serializeColumnData($this->serializationTarget, $this->getValue()));
	}

	/**
	 *
	 * @var sql\ColumnPropertyMap Literal type
	 */
	private $serializationTarget;
}