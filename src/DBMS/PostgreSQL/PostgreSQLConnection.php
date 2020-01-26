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

use NoreSources\SQL\DBMS\Connection;
use NoreSources\SQL\DBMS\ConnectionStructureTrait;
use NoreSources\SQL\DBMS\StatementParameterArray;

class PostgreSQLConnection implements Connection
{

	use ConnectionStructureTrait;

	public function __construct()
	{
		$this->resource = null;
		$this->builder = new PostgreSQLStatementBuilder($this);
	}

	public function beginTransation()
	{}

	public function commitTransation()
	{}

	public function rollbackTransaction()
	{}

	public function connect($parameters)
	{}

	public function disconnect()
	{}

	public function getStatementBuilder()
	{
		return $this->builder;
	}

	public function prepareStatement($statement)
	{}

	public function getConnectionResource()
	{
		return $this->resource;
	}

	public function executeStatement($statement, StatementParameterArray $parameters = null)
	{}

	/**
	 *
	 * @var resource
	 */
	private $resource;

	/**
	 *
	 * @var PostgreSQLStatementBuilder
	 */
	private $builder;
}