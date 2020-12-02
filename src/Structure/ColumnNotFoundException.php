<?php
/**
 * Copyright © 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

use Psr\Container\NotFoundExceptionInterface;

class ColumnNotFoundException extends \ErrorException implements
	NotFoundExceptionInterface
{

	/**
	 *
	 * @param string $name
	 */
	public function __construct($name)
	{
		$text = 'Column ';
		if (\is_integer($name))
			$text .= 'index ' . $name;
		else
			$text .= ' "' . $name . '"';

		$text .= ' not found';

		parent::__construct($text);
	}
}