<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\MySQL;

use NoreSources\Container\ChainElementTrait;
use NoreSources\SQL\DBMS\ConnectionProviderInterface;
use NoreSources\SQL\DBMS\TransactionBlockException;
use NoreSources\SQL\DBMS\TransactionBlockInterface;
use NoreSources\SQL\DBMS\Traits\ConnectionProviderTrait;
use NoreSources\SQL\DBMS\Traits\TransactionBlockTrait;

class MySQLTransactionBlock implements TransactionBlockInterface,
	ConnectionProviderInterface
{
	use TransactionBlockTrait;
	use ChainElementTrait;
	use ConnectionProviderTrait;

	public function __construct(MySQLConnection $connection, $name)
	{
		$this->setConnection($connection);
		$this->initializeTransactionBlock($name);
	}

	protected function beginTask()
	{
		if ($this->getPreviousElement() === null)
		{
			$this->executeCommand('BEGIN',
				'Failed to start transaction "' . $this->getBlockName() .
				'"');
		}

		$this->executeCommand(
			'SAVEPOINT ' .
			$this->connection->getPlatform()
				->quoteIdentifier($this->getBlockName()),
			'Failed to set transaction save point "' .
			$this->getBlockName() . '"');
	}

	protected function commitTask()
	{
		$this->executeCommand(
			'RELEASE SAVEPOINT ' .
			$this->connection->getPlatform()
				->quoteIdentifier($this->getBlockName()),
			'Failed to release save point "' . $this->getBlockName() .
			'"');

		if ($this->getPreviousElement() === null)
		{
			$this->executeCommand('COMMIT',
				'Failed to commit transaction "' . $this->getBlockName() .
				'"');
		}
	}

	protected function rollbackTask()
	{
		$this->executeCommand(
			'ROLLBACK TO ' .
			$this->connection->getPlatform()
				->quoteIdentifier($this->getBlockName()),
			'Failed to rollback save point "' . $this->getBlockName() .
			'"');

		if ($this->getPreviousElement() === null)
		{
			$this->executeCommand('ROLLBACK',
				'Failed to rollback transaction "' .
				$this->getBlockName() . '"');
		}
	}

	private function executeCommand($sql, $exceptionMessage)
	{
		/**  @var \mysqli $mysqli */
		$mysqli = $this->connection->getServerLink();
		$result = $mysqli->query($sql);

		if ($result === false)
			throw new TransactionBlockException(
				$exceptionMessage . PHP_EOL . $mysqli->errno . ': ' .
				$mysqli->error,
				TransactionBlockException::EXECUTION_ERROR);
		if ($result instanceof \mysqli_result)
			$result->free();
		return true;
	}
}
