<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\PDO;

use NoreSources\SQL\DBMS\PlatformProviderTrait;
use NoreSources\SQL\Statement\AbstractStatementBuilder;
use NoreSources\SQL\Statement\ClassMapStatementFactoryTrait;

class PDOStatementBuilder extends AbstractStatementBuilder
{

	use ClassMapStatementFactoryTrait;
	use PlatformProviderTrait;

	const DRIVER_MYSQL = PDOConnection::DRIVER_MYSQL;

	const DRIVER_POSTGRESQL = PDOConnection::DRIVER_POSTGRESQL;

	const DRIVER_SQLITE = PDOConnection::DRIVER_SQLITE;

	public function __construct(PDOConnection $connection)
	{
		parent::__construct();
		$this->initializeStatementFactory();
		$this->connection = $connection;
	}

	public function getPlatform()
	{
		return $this->connection->getPlatform();
	}

	/**
	 *
	 * @var PDOConnection $connection
	 */
	private $connection;
}