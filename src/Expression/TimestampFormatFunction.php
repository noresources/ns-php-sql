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

/**
 * Timestamp formatting meta function
 */
class TimestampFormatFunction extends MetaFunctionCall
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
		if (!($format instanceof Expression))
			$format = new Value($format, K::DATATYPE_STRING);
		if (!($timestamp instanceof Expression))
			$timestamp = new Value(new DateTime($timestamp), K::DATATYPE_TIMESTAMP);

		parent::__construct(K::METAFUNCTION_TIMESTAMP_FORMAT, [
			$format,
			$timestamp
		]);
	}
}