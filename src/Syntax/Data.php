<?php
/**
 * Copyright © 2020 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax;

use NoreSources\ComparableInterface;
use NoreSources\Expression\Value;
use NoreSources\SQL\Constants;
use NoreSources\SQL\DataTypeDescription;
use NoreSources\SQL\DataTypeProviderInterface;
use NoreSources\Type\TypeConversion;
use NoreSources\Type\TypeDescription;

class Data extends Value implements TokenizableExpressionInterface,
	DataTypeProviderInterface, ComparableInterface
{

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
		if (TypeDescription::hasStringRepresentation($v))
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

		if (TypeDescription::hasStringRepresentation($a) &&
			TypeDescription::hasStringRepresentation($b))
			return \strcmp(TypeConversion::toString($a),
				TypeConversion::toString($b));
		return -100;
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
			$dataType = Evaluator::getInstance()->getDataType(
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
