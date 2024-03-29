<?php
/**
 * Copyright © 2020 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax;

use NoreSources\ComparableInterface;
use NoreSources\Expression\Value;
use NoreSources\SQL\Constants;
use NoreSources\SQL\DataDescription;
use NoreSources\SQL\DataTypeDescription;
use NoreSources\SQL\DataTypeProviderInterface;
use NoreSources\Type\TypeComparison;
use NoreSources\Type\TypeConversion;
use NoreSources\Type\TypeConversionException;
use NoreSources\Type\TypeDescription;

class Data extends Value implements TokenizableExpressionInterface,
	DataTypeProviderInterface, ComparableInterface
{

	/**
	 * Check if the given data is NULL or a representation of NULL
	 *
	 * @param mixed $value
	 * @return boolean
	 *
	 * @deprecated Use DataDescription class directly
	 */
	public static function isNull($value)
	{
		$d = DataDescription::getInstance();
		return $d->isNull($value);
	}

	/**
	 *
	 * @param mixedn $value
	 *        	Literal value
	 * @param integer|NULLn $dataType
	 *        	Literal data type. If NULL, the data type is deducted from $value
	 */
	public function __construct($value, $dataType = null)
	{
		$this->setValue($value);
		$this->setDataType($dataType);
	}

	public function __toString()
	{
		$v = $this->getValue();
		if (TypeDescription::hasStringRepresentation($v, false))
			return TypeConversion::toString($v);
		return TypeDescription::getLocalName($v);
	}

	public function compare($b)
	{
		if ($b instanceof DataTypeProviderInterface)
		{
			$ta = $this->getDataType();
			$tb = $b->getDataType();

			if (($ta == Constants::DATATYPE_NULL) && ($ta == $tb))
				return 0;

			if ($ta != $tb)
			{
				$flags = DataTypeDescription::getInstance()->compareAffinity(
					$ta, $tb);
				if (!($flags & DataTypeDescription::AFFINITY_MATCH_ONE))
					return ($ta - $tb);
			}
		}

		if ($b instanceof Value)
			$b = $b->getValue();

		$a = $this->getValue();

		try
		{
			return TypeComparison::compare($a, $b);
		}
		catch (TypeConversionException $e)
		{}

		try
		{
			return TypeConversion::toFloat($a) -
				TypeConversion::toFloat($b);
		}
		catch (TypeConversionException $e)
		{}

		try
		{
			return TypeConversion::toInteger($a) -
				TypeConversion::toInteger($b);
		}
		catch (TypeConversionException $e)
		{}

		return ($a == $b) ? 0 : -1;
	}

	/**
	 *
	 * @param DataTypeProviderInterface|integer|NULL $dataType
	 *        	Data type. If NULL, data type is deducted from value
	 */
	public function setDataType($dataType)
	{
		if ($dataType instanceof DataTypeProviderInterface)
			$dataType = $dataType->getDataType();
		if ($dataType === null)
			$dataType = DataDescription::getInstance()->getDataType(
				$this->getValue());
		$this->dataType = $dataType;
	}

	public function getDataType()
	{
		return $this->dataType;
	}

	public function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		return $stream->literal(
			$context->getPlatform()
				->serializeData($this->getValue(), $this->dataType));
	}

	/**
	 *
	 * @var integer Data type
	 */
	private $dataType;
}
