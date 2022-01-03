<?php
/**
 * Copyright © 2020 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement;

class StatementSerializationException extends \ErrorException
{

	const CONTENT = -1;

	/**
	 *
	 * @param string $message
	 * @param number $code
	 */
	public function __construct($message, $code = 0)
	{
		parent::__construct(
			'Statement serialization error: ' . $message, $code);
	}
}
