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

use NoreSources\DateTime;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DataTypeProviderInterface;

/**
 * Timestamp formatting meta function
 */
class TimestampFormatFunction extends MetaFunctionCall implements
	DataTypeProviderInterface
{

	/**
	 *
	 * @param string $format
	 *        	Format string. The format must follow the PHP strftime supported format.
	 * @param mixed $timestamp
	 *        	Timestamp expression
	 *
	 * @see https://www.php.net/manual/en/function.strftime.php
	 * @see https://www.sqlite.org/lang_datefunc.html
	 */
	public function __construct($format, $timestamp)
	{
		if (!($format instanceof TokenizableExpressionInterface))
			$format = new Literal($format, K::DATATYPE_STRING);
		if (!($timestamp instanceof TokenizableExpressionInterface))
			$timestamp = new Literal(new DateTime($timestamp),
				K::DATATYPE_TIMESTAMP);

		parent::__construct(K::METAFUNCTION_TIMESTAMP_FORMAT,
			[
				$format,
				$timestamp
			]);
	}

	public function getDataType()
	{
		return K::DATATYPE_STRING;
	}
}