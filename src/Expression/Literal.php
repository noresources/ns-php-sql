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

use NoreSources\StringRepresentation;
use NoreSources\TypeDescription;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Structure\ArrayColumnDescription;
use NoreSources\SQL\Structure\ColumnDescriptionInterface;

/**
 * Literal value
 */
class Literal implements TokenizableExpressionInterface,
	ExpressionReturnTypeInterface, StringRepresentation
{

	/**
	 * Guess the SQL data type from the given value
	 *
	 * @param mixed $value
	 * @return integer SQL data type constant
	 */
	public static function dataTypeFromValue($value)
	{
		if (\is_integer($value))
			return K::DATATYPE_INTEGER;
		elseif (\is_float($value))
			return K::DATATYPE_FLOAT;
		elseif (\is_bool($value))
			return K::DATATYPE_BOOLEAN;
		elseif (\is_null($value))
			return K::DATATYPE_NULL;
		elseif ($value instanceof \DateTime)
			return K::DATATYPE_TIMESTAMP;
		if (\is_string($value))
			return K::DATATYPE_STRING;

		return K::DATATYPE_UNDEFINED;
	}

	/**
	 *
	 * @param mixed $value
	 *        	Literal value
	 * @param ColumnDescriptionInterface|integer|null $type
	 *        	serialization target type
	 */
	public function __construct($value, $type = K::DATATYPE_UNDEFINED)
	{
		$this->serializationTarget = K::DATATYPE_UNDEFINED;
		$this->setValue($value, $type);
	}

	public function __toString()
	{
		return \NoreSources\TypeConversion::toString($this->getValue());
	}

	/**
	 *
	 * @return mixed
	 */
	public function getValue()
	{
		return $this->literalValue;
	}

	/**
	 *
	 * @param mixed $value
	 *        	Literal value
	 * @param ColumnDescriptionInterface|integer|null $type
	 *        	Serialization target type. If NULL, the previous value is kept.
	 * @throws \LogicException
	 */
	public function setValue($value, $type = null)
	{
		if ($value instanceof TokenizableExpressionInterface)
			throw new \LogicException(
				'Literal is already an TokenizableExpressionInterface');

		$this->literalValue = $value;

		if ($type !== null)
		{
			if (\is_integer($type) && ($type == K::DATATYPE_UNDEFINED))
				$type = self::dataTypeFromValue($value);

			$this->setSerializationTarget($type);
		}
	}

	/**
	 * Set to which type the value should be serialized
	 *
	 * @param ColumnDescriptionInterface|integer $type
	 * @throws \InvalidArgumentException
	 */
	public function setSerializationTarget($type)
	{
		if ($type instanceof ColumnDescriptionInterface)
			$this->serializationTarget = $type;
		elseif (\is_integer($type))
			$this->serializationTarget = new ArrayColumnDescription(
				[
					K::COLUMN_DATA_TYPE => $type
				]);
		else
			throw new \InvalidArgumentException(
				TypeDescription::getName($type) .
				'is not a valid target argument for ' .
				TypeDescription::getName($this));
	}

	public function getExpressionDataType()
	{
		if ($this->serializationTarget instanceof ColumnDescriptionInterface)
		{
			if ($this->serializationTarget->hasColumnProperty(
				K::COLUMN_DATA_TYPE))
				return $this->serializationTarget->getColumnProperty(
					K::COLUMN_DATA_TYPE);
		}

		return ExpressionHelper::getExpressionDataType(
			$this->getValue());
	}

	public function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		return $stream->literal(
			$context->getStatementBuilder()
				->getPlatform()
				->serializeColumnData($this->serializationTarget,
				$this->getValue()));
	}

	/**
	 *
	 * @var \Noresources\SQL\Structure\ColumnDescriptionInterface Literal type
	 */
	private $serializationTarget;

	private $literalValue;
}