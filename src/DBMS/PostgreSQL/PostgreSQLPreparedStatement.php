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
use NoreSources\SQL\Syntax\Statement\ResultColumnProviderInterface;
use NoreSources\SQL\Syntax\Statement\StatementTypeProviderInterface;
use NoreSources\SQL\Syntax\Statement\Traits\StatementDataTrait;
use NoreSources\SQL\Syntax\Statement\Traits\StatementSerializationTrait;

class PostgreSQLPreparedStatement implements PreparedStatementInterface
{

	use StatementDataTrait;
	use StatementSerializationTrait;

	public static function newUniqueId()
	{
		return \uniqid('pgsql', true);
	}

	/**
	 *
	 * @param string $identifier
	 *        	Prepared statement identifier
	 * @param StatementTypeProviderInterface|ResultColumnProviderInterface|string $data
	 *        	StatementTypeProviderInterface, ResultColumnProviderInterface or SQL statement
	 *        	string
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