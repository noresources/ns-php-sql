<?php

/**
 * Copyright © 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\Test;

class SqlFormatter extends \SqlFormatter
{

	public static function format($sql, $highlight = false)
	{
		$sql = \SqlFormatter::format(\strval($sql), $highlight);
		// Fix SQLite Hex

		$sql = \preg_replace("/X\s+'(([a-fA-F0-9]{2})*)'/", 'X\'$1\'',
			$sql);

		return $sql;
	}
}
