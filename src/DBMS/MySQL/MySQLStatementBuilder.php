<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\MySQL;

use NoreSources\SQL\Statement\AbstractStatementBuilder;
use NoreSources\SQL\Statement\ClassMapStatementFactoryTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class MySQLStatementBuilder extends AbstractStatementBuilder implements
	LoggerAwareInterface
{

	use LoggerAwareTrait;
	use ClassMapStatementFactoryTrait;

	public function __construct(MySQLConnection $connection)
	{
		parent::__construct();
		$this->initializeStatementFactory();
		$this->connection = $connection;
	}

	/**
	 *
	 * @return MySQLPlatform
	 */
	public function getPlatform()
	{
		return $this->connection->getPlatform();
	}

	/**
	 *
	 * @var MySQLConnection
	 */
	private $connection;
}