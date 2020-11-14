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
use NoreSources\Expression\Value;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DataTypeProviderInterface;
use NoreSources\SQL\Structure\ColumnDescriptionInterface;

/**
 * A literal value related to a given table column
 */
class ColumnData extends Value implements
	TokenizableExpressionInterface, DataTypeProviderInterface,
	StringRepresentation
{

	/**
	 *
	 * @param mixed $value
	 *        	ColumnData value
	 * @param ColumnDescriptionInterface $target
	 *        	serialization target
	 */
	public function __construct($value,
		ColumnDescriptionInterface $target)
	{
		$this->setValue($value);
		$this->setSerializationTarget($target);
	}

	public function __toString()
	{
		return \NoreSources\TypeConversion::toString($this->getValue());
	}

	/**
	 *
	 * @return \Noresources\SQL\Structure\ColumnDescriptionInterface
	 */
	public function getSerializationTarget()
	{
		return $this->serializationTarget;
	}

	/**
	 * Set to which type the value should be serialized
	 *
	 * @param ColumnDescriptionInterface|integer $target
	 * @throws \InvalidArgumentException
	 */
	public function setSerializationTarget(
		ColumnDescriptionInterface $target)
	{
		$this->serializationTarget = $target;
	}

	public function getDataType()
	{
		return $this->serializationTarget->getColumnProperty(
			K::COLUMN_DATA_TYPE);
	}

	public function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		return $stream->literal(
			$context->getPlatform()
				->serializeColumnData($this->serializationTarget,
				$this->getValue()));
	}

	/**
	 *
	 * @var ColumnDescriptionInterface
	 */
	private $serializationTarget;
}