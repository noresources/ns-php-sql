<?php
/**
 * Copyright © 2012 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL;

use NoreSources\IntegerRepresentation;
use NoreSources\TypeConversion;
use NoreSources\MediaType\MediaTypeInterface;
use NoreSources\SQL\Constants as K;

class MediaTypeUtility
{

	/**
	 *
	 * @param mixed $value
	 * @param string|MediaTypeInterface $mediaType
	 * @return string
	 */
	public static function toString($value, $mediaType)
	{
		$s = \strval($mediaType);
		if ($mediaType instanceof MediaTypeInterface)
		{
			$syntax = $mediaType->getStructuredSyntax();
			if ($syntax !== null)
				$s = $syntax;
		}

		switch ($s)
		{
			case K::MEDIA_TYPE_HEX_STRING:
				if ($value instanceof IntegerRepresentation)
					return dechex($value->getIntegerValue());
				if (\is_integer($value))
					return dechex($value);
			break;
			case K::MEDIA_TYPE_BIT_STRING:
				if ($value instanceof IntegerRepresentation)
					return decbin($value->getIntegerValue());
				if (\is_integer($value))
					return decbin($value);
			break;
			case 'application/json':
			case 'json':
				if (\function_exists('json_encode'))
				{
					$json = @\json_encode($value);
					if (\json_last_error() == JSON_ERROR_NONE)
						return $json;
				}
			break;
			case 'application/xml':
			case 'text/xml':
			case 'xml':
				if ($value instanceof \DOMDocument)
					return $value->saveXML();
			break;
			case 'text/html':
			case 'html':
				if ($value instanceof \DOMDocument)
					return $value->saveHTML();
			break;
		}

		return TypeConversion::toString($value);
	}
}
