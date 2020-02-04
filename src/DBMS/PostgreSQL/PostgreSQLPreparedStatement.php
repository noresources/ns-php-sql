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

use NoreSources\TypeConversion;
use NoreSources\TypeDescription;
use NoreSources\SQL\DBMS\PreparedStatement;
use NoreSources\SQL\Statement\BuildContext;

class PostgreSQLPreparedStatement extends PreparedStatement
{

	public static function newUniqueId()
	{
		return \uniqid(self::class, true);
	}

	/**
	 *
	 * @param string $identifier
	 *        	Prepared statement identifier
	 * @param OutputData|string $data
	 *        	OutputData or SQL statement string
	 */
	public function __construct($identifier, $data = null)
	{
		parent::__construct($data);
		$this->identifier = $identifier;
		$this->sql = false;
		if ($data instanceof BuildContext || TypeDescription::hasStringRepresentation($data))
		{
			$this->sql = TypeConversion::toString($data);
		}
	}

	public function getStatement()
	{
		if (!\is_string($this->sql))
			throw new \RuntimeException('SQL statement string not available');

		return $this->sql;
	}

	public static function getPreparedStatementId()
	{
		return $this->identifier;
	}

	/**
	 *
	 * @var string
	 */
	private $identifier;

	/**
	 *
	 * @var string SQL statement
	 */
	private $sql;
}