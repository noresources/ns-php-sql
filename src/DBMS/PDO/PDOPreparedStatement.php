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

use NoreSources\Container;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\PreparedStatementInterface;
use NoreSources\SQL\Structure\ArrayColumnDescription;
use NoreSources\SQL\Syntax\Statement\Traits\StatementInputDataTrait;
use NoreSources\SQL\Syntax\Statement\Traits\StatementOutputDataTrait;
use NoreSources\SQL\Syntax\Statement\Traits\StatementSerializationTrait;

class PDOPreparedStatement implements PreparedStatementInterface
{

	use StatementInputDataTrait;
	use StatementOutputDataTrait;
	use StatementSerializationTrait;

	/**
	 *
	 * @param \PDOStatement $statement
	 * @param string|SQL\Syntax\StatementData $data
	 */
	public function __construct(\PDOStatement $statement, $data)
	{
		$this->initializeParameterData($data);
		$this->initializeOutputData($data);

		$this->pdoStatement = $statement;

		if ($this->getStatementType() == 0)
			$this->statementType = SQL\Syntax\Statement::statementTypeFromData(
				$data);

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
				if ($this->getResultColumns()->count() >
					$statement->columnCount())
					throw new \Exception(
						'Incorrect number of result column. Should be ' .
						$statement->columnCount() . ', got ' .
						$this->getResultColumns()->count());
			}

			$map = $this->getResultColumns();
			try
			{
				for ($i = 0; $i < $statement->columnCount(); $i++)
				{
					$meta = $statement->getColumnMeta($i);

					$column = null;
					if ($i < $map->count())
						$column = $map->get($i);
					else
					{
						$column = new ArrayColumnDescription(
							K::DATATYPE_UNDEFINED);
						$column->name = $meta['name'];
					}

					if ($column->getDataType() == K::DATATYPE_UNDEFINED)
					{
						$column->setColumnProperty(K::COLUMN_DATA_TYPE,
							PDOConnection::getDataTypeFromPDOType(
								Container::keyValue($meta, 'pdo_type',
									\PDO::PARAM_STR)));
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
		$this->pdoStatement->closeCursor();
	}

	public function __toString()
	{
		return $this->pdoStatement->queryString;
	}

	public function getPDOStatement()
	{
		return $this->pdoStatement;
	}

	/**
	 *
	 * @var \PDOStatement
	 */
	private $pdoStatement;
}