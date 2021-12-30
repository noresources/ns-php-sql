<?php

/**
 * Copyright © 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\Test;

final class SqlFormatter extends \SqlFormatter
{

	public static function format($sql, $highlight = false)
	{
		$sql = \SqlFormatter::format(\strval($sql), $highlight);

		// Fix SQLite Hex
		$sql = \preg_replace("/X\s+'(([a-fA-F0-9]{2})*)'/", 'X\'$1\'',
			$sql);

		// SQLite / Generic parameter mark
		$pattern = '(?<=^|\s|\():\s+([a-z_][a-z0-9_]+)';
		if (false)
			$sql = \preg_replace(chr(1) . $pattern . chr(1) . 'i', ':$1',
				$sql);

		// PostgreSQL parameters with data type
		$pattern = '(\$[0-9]+)\s*:\s*:\s*([a-z](?:[a-z0-9_]+))';
		$sql = \preg_replace(chr(1) . $pattern . chr(1) . 'i', '$1::$2',
			$sql);

		return $sql . PHP_EOL;
	}
}
