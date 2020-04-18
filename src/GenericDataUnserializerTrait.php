<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL;

use NoreSources\TypeConversion;
use NoreSources\MediaType\MediaType;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Structure\ColumnDescriptionInterface;

/**
 * Implements DataUnserializerInterface
 *
 * Provide flexible, overridable sub methods to unserialize certain types
 */
trait GenericDataUnserializerTrait
{

	/**
	 *
	 * @param ColumnDescriptionInterface $column
	 * @param mixed $data
	 *        	Data retrieved from DBMS storage
	 * @return mixed Unserialized data
	 */
	public function unserializeColumnData(ColumnDescriptionInterface $column, $data)
	{
		$type = K::DATATYPE_UNDEFINED;
		if ($column->hasColumnProperty(K::COLUMN_DATA_TYPE))
			$type = $column->getColumnProperty(K::COLUMN_DATA_TYPE);

		if ($type == K::DATATYPE_BINARY)
			$data = $this->unserializeBinaryColumnData($column, $data);
		elseif ($type == K::DATATYPE_BOOLEAN)
			$data = $this->unserializeBooleanColumnData($column, $data);
		elseif ($type & K::DATATYPE_TIMESTAMP)
			$data = TypeConversion::toDateTime($data);
		elseif ($type & K::DATATYPE_NUMBER)
		{
			if ($type & K::DATATYPE_FLOAT)
				$data = TypeConversion::toFloat($data);
			else
				$data = TypeConversion::toInteger($data);
		}
		elseif ($type == K::DATATYPE_NULL)
			$data = null;

		if ($column->hasColumnProperty(K::COLUMN_MEDIA_TYPE))
		{
			$mediaType = $column->getColumnProperty(K::COLUMN_MEDIA_TYPE);
			if ($mediaType instanceof MediaType)
			{
				$data = $this->unserializeStructuredSyntaxColumnData($column, $mediaType, $data);
			}
		}

		return $data;
	}

	/**
	 *
	 * @param ColumnDescriptionInterface $column
	 * @param mixed $data
	 * @return boolean
	 */
	protected function unserializeBooleanColumnData(ColumnDescriptionInterface $column, $data)
	{
		return TypeConversion::toBoolean($data);
	}

	/**
	 *
	 * @param ColumnDescriptionInterface $column
	 * @param mixed $data
	 *        	Data from DBMS storage
	 * @return mixed
	 */
	protected function unserializeBinaryColumnData(ColumnDescriptionInterface $column, $data)
	{
		return $data;
	}

	/**
	 *
	 * @param ColumnDescriptionInterface $column
	 * @param MediaType $mediaType
	 * @param mixed $data
	 *        	Text data from DBMS storage
	 * @return mixed
	 */
	protected function unserializeStructuredSyntaxColumnData(ColumnDescriptionInterface $column,
		MediaType $mediaType, $data)
	{
		$syntax = $mediaType->getStructuredSyntax();
		if ($syntax == 'json' && \function_exists('\json_encode'))
		{
			$data = \json_decode($data, true);
		}
		elseif ($syntax == 'xml' && \class_exists('\DOMDocument'))
		{
			$dom = new \DOMDocument('1.0', 'utf-8');
			$data = $dom->loadXML(data);
		}

		return $data;
	}
}