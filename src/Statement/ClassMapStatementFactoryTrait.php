<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */

//
namespace NoreSources\SQL\Statement;

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Statement\Manipulation\DeleteQuery;
use NoreSources\SQL\Statement\Manipulation\InsertQuery;
use NoreSources\SQL\Statement\Manipulation\UpdateQuery;
use NoreSources\SQL\Statement\Query\SelectQuery;
use NoreSources\SQL\Statement\Structure\CreateIndexQuery;
use NoreSources\SQL\Statement\Structure\CreateTableQuery;
use NoreSources\SQL\Statement\Structure\DropTableQuery;

/**
 * Implements StatementFactoryInterface
 */
trait ClassMapStatementFactoryTrait
{

	/**
	 *
	 * @param integer $statementType
	 *        	Statement type
	 * @throws \InvalidArgumentException
	 * @return Statement
	 */
	public function newStatement($statementType)
	{
		if (!$this->statementClassMap->offsetExists($statementType))
			throw new \InvalidArgumentException('Unsupported statement type ' . $statementType);

		$args = func_get_args();
		array_shift($args);

		$cls = new \ReflectionClass($this->statementClassMap[$statementType]);
		return $cls->newInstanceArgs($args);
	}

	protected function initializeStatementFactory($classMap = array())
	{
		$this->statementClassMap = new \ArrayObject(
			[
				//K::QUERY_CREATE_INDEX => null,
				K::QUERY_CREATE_TABLE => CreateTableQuery::class,
				K::QUERY_CREATE_INDEX => CreateIndexQuery::class,
				K::QUERY_SELECT => SelectQuery::class,
				K::QUERY_INSERT => InsertQuery::class,
				K::QUERY_UPDATE => UpdateQuery::class,
				K::QUERY_DELETE => DeleteQuery::class,
				K::QUERY_DROP_TABLE => DropTableQuery::class
			]);

		foreach ($classMap as $type => $cls)
		{
			$this->statementClassMap->offsetSet($type, $cls);
		}
	}

	/**
	 *
	 * @var \ArrayObject
	 */
	private $statementClassMap;
}