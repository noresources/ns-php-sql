<?php
namespace NoreSources\SQL\DBMS;

use NoreSources\DateTime;
use NoreSources\TypeDescription;

/**
 * A special container dedicated to store PHP timestamp format token translation.
 */
class TimestampFormatTranslationMap extends \ArrayObject
{

	/**
	 *
	 * @param \Traversable $array
	 *        	PHP date() format tokenn translation data
	 */
	public function __construct($array)
	{
		foreach ($array as $key => $data)
		{
			$this->offsetSet($key, $data);
		}
	}

	/**
	 *
	 * @param string $key
	 * @return true if $key is a valid PHP date() token
	 *
	 * @see https://www.php.net/manual/en/datetime.format.php
	 */
	public static function isToken($key)
	{
		return DateTime::getFormatTokenDescriptions()->offsetExists(
			$key);
	}

	public function offsetExists($index)
	{
		return self::isToken($index);
	}

	public function offsetGet($index)
	{
		if (parent::offsetExists($index))
			return parent::offsetGet($index);
		return false;
	}

	public function offsetSet($key, $data)
	{
		if (!self::isToken($key))
			throw new \InvalidArgumentException(
				$key . ' is not a valid PHP date format token');
		if (!($data === false || \is_string($data) ||
			(\is_array($data) && \count($data) >= 2)))
			throw new \InvalidArgumentException(
				'Invalid translation data for token "' . $key .
				'". false, string or array with 2 elements expected. Got ' .
				TypeDescription::getName($data));
		parent::offsetSet($key, $data);
	}
}
