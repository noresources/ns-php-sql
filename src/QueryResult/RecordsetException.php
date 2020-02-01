<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\QueryResult;

class RecordsetException extends \ErrorException
{

	/**
	 *
	 * @var Recordset
	 */
	public $recordset;

	/**
	 *
	 * @param Recordset $recordset
	 * @param string $message
	 * @param integer $code
	 */
	public function __construct(Recordset $recordset, $message, $code = null)
	{
		parent::__construct($message, $code);
		$this->recordset = $recordset;
	}
}