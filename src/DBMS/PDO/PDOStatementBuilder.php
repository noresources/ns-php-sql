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
use NoreSources\SQL\Statement\ParameterData;

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

	public function serializeString($value)
	{
		$o = null;
		if ($this->connection instanceof PDOConnection)
			$o = $this->connection->getConnectionObject();

		if ($o instanceof \PDO)
			return $o->quote($value);

		return "'" . self::escapeString($value) . "'";
	}

	public function escapeIdentifier($identifier)
	{
		switch ($this->driverName)
		{
			case self::DRIVER_POSTGRESQL:
				return '"' . $identifier . '"';
			case self::DRIVER_MYSQL:
				return '`' . $identifier . '`';
			case self::DRIVER_SQLITE:
				return '[' . $identifier . ']';
		}
		return $identifier;
	}

	public function configure(\PDO $connection)
	{
		$this->driverName = $connection->getAttribute(
			\PDO::ATTR_DRIVER_NAME);
	}

	public function getParameter($name, ParameterData $parameters = null)
	{
		return (':' . $parameters->count());
	}

	private $driverName;

	/**
	 *
	 * @var PDOConnection $connection
	 */
	private $connection;
}