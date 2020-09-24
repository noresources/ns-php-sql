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

use NoreSources\ChainElementTrait;
use NoreSources\SQL\DBMS\ConnectionProviderInterface;
use NoreSources\SQL\DBMS\ConnectionProviderTrait;
use NoreSources\SQL\DBMS\TransactionBlockException;
use NoreSources\SQL\DBMS\TransactionBlockInterface;
use NoreSources\SQL\DBMS\TransactionBlockTrait;

class PostgreSQLTransactionBlock implements TransactionBlockInterface,
	ConnectionProviderInterface
{
	use TransactionBlockTrait;
	use ChainElementTrait;
	use ConnectionProviderTrait;

	public function __construct(PostgreSQLConnection $connection, $name)
	{
		$this->setConnection($connection);
		$this->initializeTransactionBlock($name);
		$this->blockIdentifier = $connection->getStatementBuilder()
			->getPlatform()
			->quoteIdentifier($name);
	}

	protected function beginTask()
	{
		$pg = $this->getConnection()->getConnectionResource();
		if ($this->getPreviousElement() === null)
		{
			$result = \pg_query($pg, 'BEGIN');
			if ($result === false)
				throw new TransactionBlockException(\pg_last_error($pg));

			if (\pg_result_status($result) != PGSQL_COMMAND_OK)
			{
				$s = \pg_result_status($result, PGSQL_STATUS_STRING);
				pg_free_result($result);
				throw new TransactionBlockException($s);
			}

			pg_free_result($result);
		}

		$result = \pg_query($pg, 'SAVEPOINT ' . $this->blockIdentifier);
		if ($result === false)
			throw new TransactionBlockException(\pg_last_error($pg));

		if (\pg_result_status($result) != PGSQL_COMMAND_OK)
		{
			$s = \pg_result_status($result, PGSQL_STATUS_STRING);
			pg_free_result($result);
			throw new TransactionBlockException($s);
		}

		pg_free_result($result);
	}

	protected function commitTask()
	{
		$pg = $this->getConnection()->getConnectionResource();
		$result = \pg_query($pg, 'RELEASE ' . $this->blockIdentifier);
		if ($result === false)
			throw new TransactionBlockException(\pg_last_error($pg));

		if (\pg_result_status($result) != PGSQL_COMMAND_OK)
		{
			$s = \pg_result_status($result, PGSQL_STATUS_STRING);
			pg_free_result($result);
			throw new TransactionBlockException($s);
		}

		pg_free_result($result);

		if ($this->getPreviousElement() === null)
		{
			$result = \pg_query($pg, 'COMMIT');
			if ($result === false)
				throw new TransactionBlockException(\pg_last_error($pg));

			if (\pg_result_status($result) != PGSQL_COMMAND_OK)
			{
				$s = \pg_result_status($result, PGSQL_STATUS_STRING);
				pg_free_result($result);
				throw new TransactionBlockException($s);
			}

			pg_free_result($result);
		}
	}

	protected function rollbackTask()
	{
		$pg = $this->getConnection()->getConnectionResource();
		$s = 'ROLLBACK';
		if ($this->getPreviousElement() instanceof TransactionBlockInterface)
		{
			$s .= ' TO ' . $this->blockIdentifier;
		}

		$result = \pg_query($pg, $s);
		if ($result === false)
			throw new TransactionBlockException(\pg_last_error($pg));

		if (\pg_result_status($result) != PGSQL_COMMAND_OK)
		{
			$s = \pg_result_status($result, PGSQL_STATUS_STRING);
			pg_free_result($result);
			throw new TransactionBlockException($s);
		}

		pg_free_result($result);
	}

	/**
	 *
	 * @var string
	 */
	private $blockIdentifier;
}