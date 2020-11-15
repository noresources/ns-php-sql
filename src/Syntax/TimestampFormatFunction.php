<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax;

use NoreSources\DateTime;
use NoreSources\Expression\ExpressionInterface;
use NoreSources\Expression\Value;
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
	 * @param Evaluable $format
	 *        	Format string. The format must follow the PHP strftime supported format.
	 * @param Evaluable $timestamp
	 *        	Timestamp expression
	 *
	 * @see https://www.php.net/manual/en/function.strftime.php
	 * @see https://www.sqlite.org/lang_datefunc.html
	 */
	public function __construct($format, $timestamp)
	{
		if (!($format instanceof Value))
			$format = new Value($format);

		if (!($timestamp instanceof ExpressionInterface))
			$timestamp = new Data(new DateTime($timestamp),
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