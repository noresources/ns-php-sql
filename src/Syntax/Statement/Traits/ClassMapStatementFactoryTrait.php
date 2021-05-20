<?php
/**
 * Copyright © 2020 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement\Traits;

use NoreSources\Container;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Syntax\Statement\Statement;
use NoreSources\SQL\Syntax\Statement\StatementNotAvailableException;
use NoreSources\SQL\Syntax\Statement\Manipulation\DeleteQuery;
use NoreSources\SQL\Syntax\Statement\Manipulation\InsertQuery;
use NoreSources\SQL\Syntax\Statement\Manipulation\UpdateQuery;
use NoreSources\SQL\Syntax\Statement\Query\SelectQuery;
use NoreSources\SQL\Syntax\Statement\Structure\CreateIndexQuery;
use NoreSources\SQL\Syntax\Statement\Structure\CreateNamespaceQuery;
use NoreSources\SQL\Syntax\Statement\Structure\CreateTableQuery;
use NoreSources\SQL\Syntax\Statement\Structure\DropIndexQuery;
use NoreSources\SQL\Syntax\Statement\Structure\DropNamespaceQuery;
use NoreSources\SQL\Syntax\Statement\Structure\DropTableQuery;

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
		if (!Container::keyExists($this->statementClassMap,
			$statementType))
			throw new StatementNotAvailableException($statementType);

		$args = func_get_args();
		array_shift($args);

		$cls = new \ReflectionClass(
			$this->statementClassMap[$statementType]);
		return $cls->newInstanceArgs($args);
	}

	/**
	 *
	 * @param string $statementType
	 *        	Statement base class
	 * @return boolean
	 */
	public function hasStatement($statementType)
	{
		return Container::keyExists($this->statementClassMap,
			$statementType);
	}

	protected function initializeStatementFactory($classMap = array())
	{
		$this->statementClassMap = \array_merge(
			[
				// K::QUERY_CREATE_INDEX => null,
				K::QUERY_CREATE_TABLE => CreateTableQuery::class,
				CreateTableQuery::class => CreateTableQuery::class,
				K::QUERY_CREATE_INDEX => CreateIndexQuery::class,
				CreateIndexQuery::class => CreateIndexQuery::class,
				K::QUERY_CREATE_NAMESPACE => CreateNamespaceQuery::class,
				CreateNamespaceQuery::class => CreateNamespaceQuery::class,
				K::QUERY_SELECT => SelectQuery::class,
				SelectQuery::class => SelectQuery::class,
				K::QUERY_INSERT => InsertQuery::class,
				InsertQuery::class => InsertQuery::class,
				K::QUERY_UPDATE => UpdateQuery::class,
				UpdateQuery::class => UpdateQuery::class,
				K::QUERY_DELETE => DeleteQuery::class,
				DeleteQuery::class => DeleteQuery::class,
				K::QUERY_DROP_NAMESPACE => DropNamespaceQuery::class,
				DropNamespaceQuery::class => DropNamespaceQuery::class,
				K::QUERY_DROP_TABLE => DropTableQuery::class,
				DropTableQuery::class => DropTableQuery::class,
				K::QUERY_DROP_INDEX => DropIndexQuery::class,
				DropIndexQuery::class => DropIndexQuery::class
			], $classMap);
	}

	/**
	 *
	 * @var array
	 */
	private $statementClassMap;
}