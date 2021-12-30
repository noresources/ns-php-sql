<?php
namespace NoreSources\SQL;

use NoreSources\ComparableInterface;
use NoreSources\NotComparableException;
use NoreSources\SingletonTrait;
use NoreSources\Expression\Value;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Syntax\Keyword;
use NoreSources\Type\BooleanRepresentation;
use NoreSources\Type\FloatRepresentation;
use NoreSources\Type\IntegerRepresentation;
use NoreSources\Type\TypeConversion;
use NoreSources\Type\TypeDescription;

class DataDescription
{
	use SingletonTrait;

	/**
	 * Check if the given data is NULL or a representation of NULL
	 *
	 * @param mixed $value
	 * @return boolean
	 */
	public function isNull($value)
	{
		if ($value instanceof DataTypeProviderInterface)
		{
			if ($value->getDataType() == K::DATATYPE_NULL)
				return true;
		}

		if ($value instanceof Value)
			$value = $value->getValue();

		if ($value === null)
			return true;
		return false;
	}

	public function isSimilar($a, $b)
	{
		if ($a instanceof ComparableInterface)
		{
			try
			{
				$v = $a->compare($b) == 0;
				if ($v)
					return true;
			}
			catch (NotComparableException $e)
			{}
		}
		elseif ($b instanceof ComparableInterface)
		{
			try
			{
				$v = $b->compare($a) == 0;
				if ($v)
					return true;
			}
			catch (NotComparableException $e)
			{}
		}

		$av = null;
		$bv = null;
		try
		{
			$at = $this->getDataType($a);
			$bt = $this->getDataType($b);
			$av = $this->getValue($a);
			$bv = $this->getValue($b);

			if ($at == $bt)
				return ($av == $bv);
		}
		catch (\Exception $e)
		{
			return false;
		}

		try
		{
			$v = $this->similarityCheck($av, $bv);
			if ($v)
				return true;
		}
		catch (\Exception $e)
		{}

		try
		{
			$v = $this->similarityCheck($bv, $av);
		}
		catch (\Exception $e)
		{}

		return $v;
	}

	/**
	 *
	 * @param mixed $expression
	 *        	Expression
	 * @throws TypeConversionException::
	 * @return mixed
	 */
	public function getValue($expression)
	{
		if ($expression instanceof Value)
			return $expression->getValue();

		$dataType = $this->getDataType($expression);
		if ($dataType == K::DATATYPE_NULL)
			return null;
		$dataType &= ~K::DATATYPE_NULL;

		if ($expression instanceof Keyword)
			return $expression->getValue();
		switch ($dataType)
		{
			case K::DATATYPE_BOOLEAN:
				return TypeConversion::toBoolean($expression);
			case K::DATATYPE_INTEGER:
				return TypeConversion::toInteger($expression);
			case K::DATATYPE_REAL:
			case K::DATATYPE_NUMBER:
				return TypeConversion::toFloat($expression);
			case K::DATATYPE_STRING:
			case K::DATATYPE_DATE:
			case K::DATATYPE_TIME:
			case K::DATATYPE_TIMESTAMP:
			case K::DATATYPE_STRING:
				return TypeConversion::toString($expression);
		}
		return $expression;
	}

	/**
	 *
	 * @param mixed $expression
	 *        	Any object
	 * @return integer Data type identifier
	 */
	public function getDataType($expression)
	{
		if ($expression instanceof DataTypeProviderInterface)
			return $expression->getDataType();
		elseif (\is_object($expression))
		{
			if ($expression instanceof \DateTimeInterface)
				return K::DATATYPE_TIMESTAMP;
			elseif (TypeDescription::hasStringRepresentation(
				$expression))
				return K::DATATYPE_STRING;
			elseif ($expression instanceof FloatRepresentation)
				return K::DATATYPE_REAL;
			elseif ($expression instanceof IntegerRepresentation)
				return K::DATATYPE_INTEGER;
			elseif ($expression instanceof BooleanRepresentation)
				return K::DATATYPE_BOOLEAN;
		}

		if (\is_integer($expression))
			return K::DATATYPE_INTEGER;
		elseif (\is_float($expression))
			return K::DATATYPE_REAL;
		elseif (\is_bool($expression))
			return K::DATATYPE_BOOLEAN;
		elseif (\is_null($expression))
			return K::DATATYPE_NULL;
		elseif (\is_string($expression))
			return K::DATATYPE_STRING;

		return K::DATATYPE_UNDEFINED;
	}

	protected function similarityCheck($av, $bv)
	{
		if (\is_null($av))
			return empty($bv);
		if (\is_bool($av))
		{
			$bv = TypeConversion::toInteger($bv);
			return ($av) ? ($bv == 1) : ($bv == 0);
		}
		elseif (\is_integer($av) || \is_float($av))
			return TypeConversion::toFloat($av) ==
				TypeConversion::toFloat($bv);

		if ($a instanceof ComparableInterface)
			return $a->compare($b) == 0;

		return TypeConversion::toString($av) ==
			TypeConversion::toString($bv);
	}
}
