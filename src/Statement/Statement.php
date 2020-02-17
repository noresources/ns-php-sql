<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */

// Namespace
namespace NoreSources\SQL\Statement;

// Aliases
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Expression as X;
use NoreSources\SQL\DBMS\PreparedStatement;

/**
 * Exception raised while building statement SQL string
 */
class StatementException extends \Exception
{

	public function __construct(Statement $statement, $message)
	{
		parent::__construct($message);
		$this->statement = $statement;
	}

	/**
	 *
	 * @return \NoreSources\SQL\Statement
	 */
	public function getStatement()
	{
		return $this->statement;
	}

	/**
	 *
	 * @var Statement
	 */
	private $statement;
}

/**
 * SQL statement
 */
abstract class Statement implements X\Expression
{

	/**
	 * Attempt to guess statement type from statement data
	 *
	 * @param mixed $data
	 * @return string|unknown|number
	 */
	public static function statementTypeFromData($data)
	{
		if (\is_object($data))
		{
			if ($data instanceof SelectQuery)
				return K::QUERY_SELECT;
			elseif ($data instanceof InsertQuery)
				return K::QUERY_INSERT;
			elseif ($data instanceof UpdateQuery)
				return K::QUERY_UPDATE;
			elseif ($data instanceof DeleteQuery)
				return K::QUERY_DELETE;
			elseif ($data instanceof CreateTableQuery)
				return K::QUERY_CREATE_TABLE;
			elseif ($data instanceof DropTableQuery)
				return K::QUERY_DROP_TABLE;

			$type = 0;
			if ($data instanceof OutputData)
				$type = $data->getStatementType();

			if ($type != 0)
				return $type;

			if ($data instanceof PreparedStatement)
				$data = $data->getStatement();
			elseif ($data instanceof BuildContext)
				$data = strval($data);
		}

		if (\is_string($data))
		{
			$regex = [
				'/^select\s+/i' => K::QUERY_SELECT,
				'/^insert\s+/i' => K::QUERY_INSERT,
				'/^update\s+/i' => K::QUERY_UPDATE,
				'/^delete\s+/i' => K::QUERY_DELETE,
				'/^create\s+/i' => K::QUERY_FAMILY_CREATE
			];

			foreach ($regex as $r => $t)
			{
				if (preg_match($r, $data))
					return $t;
			}
		}

		return 0;
	}
}

