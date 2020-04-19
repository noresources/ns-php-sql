<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\Reference;


use NoreSources\SQL\DBMS\PreparedStatement;

/**
 * SQLite3 implementation of NoreSources\SQL\ReferencePreparedStatement
 */
class ReferencePreparedStatement extends PreparedStatement
{

	/**
	 *
	 * @param \NoreSources\SQL\Statement\ParameterDataAwareInterface $data
	 */
	public function __construct($data)
	{
		parent::__construct($data);
		$this->sql = strval($data);
	}

	public function getStatement()
	{
		return $this->sql;
	}

	/**
	 *
	 * @var string
	 */
	private $sql;
}