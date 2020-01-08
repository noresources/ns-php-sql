<?php
/**
 * Copyright © 2012-2018 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\Expression;

use NoreSources\Expression as xpr;
use NoreSources\TypeDescription;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Statement\BuildContext;
use NoreSources\SQL\Structure\ArrayColumnPropertyMap;
use NoreSources\SQL\Structure\ColumnPropertyMap;

class Value extends xpr\Value implements Expression, ExpressionReturnType
{

	public function __construct($value, $type = K::DATATYPE_STRING)
	{
		parent::__construct($value);
		$this->setSerializationTarget($type);
		if ($value instanceof Expression)
			throw new \LogicException('Value is already an Expression');
	}

	/**
	 * Set to which type the value should be serialized
	 *
	 * @param ArrayColumnPropertyMap|integer $type
	 * @throws \InvalidArgumentException
	 */
	public function setSerializationTarget($type)
	{
		if ($type instanceof ColumnPropertyMap)
			$this->serializationTarget = $type;
		elseif (\is_integer($type))
			$this->serializationTarget = new ArrayColumnPropertyMap(
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
		if ($this->serializationTarget instanceof ColumnPropertyMap)
		{
			if ($this->serializationTarget->hasColumnProperty(K::COLUMN_PROPERTY_DATA_TYPE))
				return $this->serializationTarget->getColumnProperty(K::COLUMN_PROPERTY_DATA_TYPE);
		}

		return Helper::getExpressionDataType($this->getValue());
	}

	public function tokenize(TokenStream $stream, BuildContext $context)
	{
		return $stream->literal(
			$context->serializeColumnData($this->serializationTarget, $this->getValue()));
	}

	/**
	 *
	 * @var ColumnPropertyMap Literal type
	 */
	private $serializationTarget;
}