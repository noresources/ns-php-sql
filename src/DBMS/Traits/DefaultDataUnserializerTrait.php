<?php
/**
 * Copyright © 2012 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\Traits;

use NoreSources\Container\Container;
use NoreSources\MediaType\MediaType;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DataTypeProviderInterface;
use NoreSources\Text\StructuredText;
use NoreSources\Type\TypeConversion;
use NoreSources\Type\TypeConversionException;

/**
 * Implements DataUnserializerInterface
 *
 * Provide flexible, overridable sub methods to unserialize certain types
 */
trait DefaultDataUnserializerTrait
{

	/**
	 *
	 * @param mixed $columnDescription
	 * @param mixed $data
	 *        	Data retrieved from DBMS storage
	 * @return mixed Unserialized data
	 */
	public function unserializeColumnData($columnDescription, $data)
	{
		$dataType = K::DATATYPE_UNDEFINED;
		if ($columnDescription instanceof DataTypeProviderInterface)
			$dataType = $columnDescription->getDataType();
		else
			$dataType = Container::keyValue($columnDescription,
				K::COLUMN_DATA_TYPE, $dataType);

		if ($dataType == K::DATATYPE_NULL)
			$data = null;
		$dataType &= ~K::DATATYPE_NULL;

		if ($dataType == K::DATATYPE_BINARY)
			$data = $this->unserializeBinaryColumnData(
				$columnDescription, $data);
		elseif ($dataType == K::DATATYPE_BOOLEAN)
			$data = $this->unserializeBooleanColumnData(
				$columnDescription, $data);
		elseif ($dataType & K::DATATYPE_TIMESTAMP)
			$data = TypeConversion::toDateTime($data);
		elseif ($dataType & K::DATATYPE_NUMBER)
		{
			if ($dataType & K::DATATYPE_FLOAT)
				$data = TypeConversion::toFloat($data);
			else
				$data = TypeConversion::toInteger($data);
		}

		if (($mediaType = Container::keyValue($columnDescription,
			K::COLUMN_MEDIA_TYPE)))
		{
			if ($mediaType instanceof MediaType)
			{
				$data = $this->unserializeStructuredSyntaxColumnData(
					$columnDescription, $mediaType, $data);
			}
		}

		return $data;
	}

	/**
	 *
	 * @param mixed $column
	 * @param mixed $data
	 * @return boolean
	 */
	protected function unserializeBooleanColumnData($column, $data)
	{
		return TypeConversion::toBoolean($data);
	}

	/**
	 *
	 * @param mixed $column
	 * @param mixed $data
	 *        	Data from DBMS storage
	 * @return mixed
	 */
	protected function unserializeBinaryColumnData($column, $data)
	{
		if (\is_resource($data) && \get_resource_type($data))
			return \stream_get_contents($data);
		return $data;
	}

	/**
	 *
	 * @param mixed $column
	 * @param MediaType $mediaType
	 * @param mixed $data
	 *        	Text data from DBMS storage
	 * @return mixed
	 */
	protected function unserializeStructuredSyntaxColumnData($column,
		MediaType $mediaType, $data)
	{
		$syntax = $mediaType->getStructuredSyntax();
		if ($syntax === null)
			$syntax = \strval($mediaType->getSubType());
		try
		{
			return StructuredText::parseText($data, $syntax);
		}
		catch (TypeConversionException $e)
		{}

		/**
		 *
		 * @todo Wait XML support in ns-php-core StructuredText
		 */
		if ($syntax == 'xml' && \class_exists('\DOMDocument'))
		{
			$dom = new \DOMDocument('1.0', 'utf-8');
			$data = $dom->loadXML(data);
		}

		return $data;
	}
}