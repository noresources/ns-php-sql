<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS;

class TypeException extends \Exception
{

	/**
	 *
	 * @var TypeInterface
	 */
	public $type;

	public function __construct(TypeInterface $type = null, $message)
	{
		parent::__construct($message);
		$this->type = $type;
	}
}