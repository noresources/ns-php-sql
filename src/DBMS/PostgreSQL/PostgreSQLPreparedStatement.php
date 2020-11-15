<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\PostgreSQL;

use NoreSources\SQL\DBMS\PreparedStatementInterface;
use NoreSources\SQL\Syntax\Statement\StatementOutputDataInterface;
use NoreSources\SQL\Syntax\Statement\Traits\StatementDataTrait;

class PostgreSQLPreparedStatement implements PreparedStatementInterface
{

	use StatementDataTrait;

	public static function newUniqueId()
	{
		return \uniqid('pgsql', true);
	}

	/**
	 *
	 * @param string $identifier
	 *        	Prepared statement identifier
	 * @param StatementOutputDataInterface|string $data
	 *        	StatementOutputDataInterface or SQL statement string
	 */
	public function __construct($identifier, $data = null)
	{
		$this->initializeStatementData($data);
		$this->identifier = $identifier;
	}

	public function getPreparedStatementId()
	{
		return $this->identifier;
	}

	/**
	 *
	 * @var string
	 */
	private $identifier;
}