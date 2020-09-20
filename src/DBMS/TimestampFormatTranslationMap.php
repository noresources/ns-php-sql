<?php
namespace NoreSources\SQL\DBMS;

use NoreSources\Container;
use NoreSources\DateTime;

class TimestampFormatTranslationMap extends \ArrayObject
{

	public function __construct($array)
	{
		$entries = [
			DateTime::FORMAT_YEAR_LEAP => false,
			DateTime::FORMAT_YEAR_ISO8601 => false,
			DateTime::FORMAT_YEAR_DIGIT_2 => false,
			DateTime::FORMAT_YEAR_NUMBER => false,
			DateTime::FORMAT_YEAR_DAY_NUMBER => false,
			DateTime::FORMAT_MONTH_ALPHA_3 => false,
			DateTime::FORMAT_MONTH_NAME => false,
			DateTime::FORMAT_MONTH_DIGIT_2 => false,
			DateTime::FORMAT_MONTH_NUMBER => false,
			DateTime::FORMAT_MONTH_DAY_COUNT => false,
			DateTime::FORMAT_WEEK_DIGIT_2 => false,
			DateTime::FORMAT_WEEK_DAY_ISO8601 => false,
			DateTime::FORMAT_WEEK_DAY_NUMBER => false,
			DateTime::FORMAT_WEEK_DAY_EN_ALPHA_2 => false,
			DateTime::FORMAT_DAY_ALPHA_3 => false,
			DateTime::FORMAT_DAY_NAME => false,
			DateTime::FORMAT_DAY_DIGIT_2 => false,
			DateTime::FORMAT_DAY_NUMBER => false,
			DateTime::FORMAT_HOUR_24_DIGIT_2 => false,
			DateTime::FORMAT_HOUR_24_PADDED => false,
			DateTime::FORMAT_HOUR_12_DIGIT_2 => false,
			DateTime::FORMAT_HOUR_12_PADDED => false,
			DateTime::FORMAT_SWATCH_TIME => false,
			DateTime::FORMAT_EPOCH_OFFSET => false,
			DateTime::FORMAT_HOUR_AM_UPPERCASE => false,
			DateTime::FORMAT_HOUR_AM_LOWERCASE => false,
			DateTime::FORMAT_MINUTE_DIGIT_2 => false,
			DateTime::FORMAT_SECOND_DIGIT_2 => false,
			DateTime::FORMAT_MILLISECOND => false,
			DateTime::FORMAT_MICROSECOND => false,
			DateTime::FORMAT_TIMEZONE_OFFSET => false,
			DateTime::FORMAT_TIMEZONE_GMT_OFFSET_COLON => false,
			DateTime::FORMAT_TIMEZONE_GMT_OFFSET => false,
			DateTime::FORMAT_TIMEZONE_NAME => false,
			DateTime::FORMAT_TIMEZONE_DST => false,
			DateTime::FORMAT_TIMEZONE_ALPHA_3 => false,
			DateTime::FORMAT_TIMESTAMP_ISO8601 => false,
			DateTime::FORMAT_TIMESTAMP_RFC2822 => false
		];

		parent::__construct(
			\array_merge($entries,
				Container::filter($array,
					function ($k, $v) use ($entries) {
						return Container::keyExists($entries, $k);
					})));
	}
}
