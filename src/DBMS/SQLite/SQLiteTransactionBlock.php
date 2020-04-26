<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\SQLite;

use NoreSources\ChainElementTrait;
use NoreSources\SQL\DBMS\ConnectionAwareInterface;
use NoreSources\SQL\DBMS\ConnectionAwareTrait;
use NoreSources\SQL\DBMS\TransactionBlockException;
use NoreSources\SQL\DBMS\TransactionBlockInterface;
use NoreSources\SQL\DBMS\TransactionBlockTrait;

/**
 *
 * @see https://sqlite.org/lang_transaction.html
 *
 */
class SQLiteTransactionBlock implements TransactionBlockInterface, ConnectionAwareInterface
{

	use TransactionBlockTrait;
	use ChainElementTrait;
	use ConnectionAwareTrait;

	public function __construct(SQLiteConnection $connection, $name)
	{
		$this->initializeTransactionBlock($name);
		$this->setConnection($connection);
	}

	protected function beginTask()
	{
		if ($this->getPreviousElement() === null)
			$this->executeCommand('BEGIN');
		$this->executeCommand(
			'SAVEPOINT ' .
			$this->connection->getStatementBuilder()
				->escapeIdentifier($this->getBlockName()));
	}

	protected function commitTask()
	{
		$this->executeCommand(
			'RELEASE ' .
			$this->connection->getStatementBuilder()
				->escapeIdentifier($this->getBlockName()));
		if ($this->getPreviousElement() === null)
			$this->executeCommand('COMMIT');
	}

	protected function rollbackTask()
	{
		$this->executeCommand(
			'ROLLBACK TO ' .
			$this->connection->getStatementBuilder()
				->escapeIdentifier($this->getBlockName()));
		if ($this->getPreviousElement() === null)
			$this->executeCommand('ROLLBACK');
	}

	private function executeCommand($sql)
	{
		/**
		 *
		 * @var \SQLite3 $sqlite
		 */
		$sqlite = $this->getConnection()->getSQLite();

		$result = $sqlite->exec($sql);
		if ($result === false)
			throw new TransactionBlockException($sqlite->lastErrorMsg(),
				TransactionBlockException::EXECUTION_ERROR);
	}
}