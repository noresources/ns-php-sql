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

// Aliases
use NoreSources\Container;
use NoreSources\TypeDescription;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS;
use NoreSources\SQL\Statement\ResultColumn;
use NoreSources\SQL\Statement\Statement;

class PDOPreparedStatement extends DBMS\PreparedStatement
{

	/**
	 *
	 * @param \PDOStatement $statement
	 * @param string|SQL\StatementData $data
	 */
	public function __construct(\PDOStatement $statement, $data)
	{
		parent::__construct($data);
		$this->statement = $statement;
		$this->statementOwner = null;

		if ($this->getStatementType() == 0)
			$this->statementType = SQL\Statement::statementTypeFromData($data);

		if ($this->getStatementType() == K::QUERY_SELECT)
		{
			/**
			 * From PHP documentation
			 * If the PDOStatement object was returned from PDO::prepare(),
			 * an accurate column count will not be available
			 * until you invoke PDOStatement::execute().
			 */

			if ($statement->columnCount() > 0)
			{
				if ($this->getResultColumns()->count() > $statement->columnCount())
					throw new \Exception(
						'Incorrect number of result column. Should be ' . $statement->columnCount() .
						', got ' . $this->getResultColumns()->count());
			}

			$map = $this->getResultColumns();
			try
			{
				for ($i = 0; $i < $statement->columnCount(); $i++)
				{
					$meta = $statement->getColumnMeta($i);

					$column = null;
					if ($i < $map->count())
						$column = $map->getColumn($i);
					else
					{
						$column = new ResultColumn(K::DATATYPE_UNDEFINED);
						$column->name = $meta['name'];
					}

					if ($column->dataType == K::DATATYPE_UNDEFINED)
					{
						$column->dataType = PDOConnection::getDataTypeFromPDOType(
							Container::keyValue($meta, 'pdo_type', \PDO::PARAM_STR));
						$map->setColumn($i, $column);
					}

					if ($i >= $map->count())
						$map->setColumn($i, $column);
				}
			}
			catch (\Exception $e)
			{}
		}
	}

	public function __destruct()
	{
		$this->statement->closeCursor();
	}

	public function getStatement()
	{
		return $this->statement->queryString;
	}

	public function acquirePDOStatement($by)
	{
		if ($this->statementOwner !== null)
		{
			if ($this->statementOwner !== $by)
			{
				throw new \LogicException(
					'Statement is already acquired by ' .
					TypeDescription::getName($this->statementOwner));
			}
		}

		$this->statementOwner = $by;
	}

	public function releasePDOStatement($by)
	{
		if ($this->statementOwner === null)
		{
			if ($this->statementOwner !== $by)
			{
				throw new \LogicException(
					TypeDescription::getName($by) . ' is not the owner of the PDOStatement');
			}
		}

		$this->statementOwner = null;
	}

	public function isPDOStatementAcquired()
	{
		return ($this->statementOwner !== null);
	}

	public function getPDOStatement()
	{
		return $this->statement;
	}

	/**
	 *
	 * @var \PDOStatement
	 */
	private $statement;

	private $statementOwner;
}