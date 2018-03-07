<?php

/**
 * Copyright © 2012-2018 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */

/**
 * A set of ns\IExpression designed to be used in queries
 *
 * @package SQL
 */
namespace NoreSources\SQL;

use NoreSources as ns;

/**
 * DBMS-type dependant SQL Query interface
 *
 * Provides
 * - query execution
 * - query fetching (return a query result)
 */
abstract class IQuery
{

	/**
	 * DBMS reference
	 *
	 * @return Datasource
	 */
	public function getDatasource()
	{
		return $this->datasource;
	}

	/**
	 *
	 * @param Datasource $a_datasource
	 */
	protected function __construct(Datasource $a_datasource)
	{
		$this->datasource = $a_datasource;
	}

	public function __get($member)
	{
		if ($member == 'datasource')
			return $this->datasource;
		
		throw new \InvalidArgumentException($member);
	}

	public function __invoke($flags = 0)
	{
		return $this->execute($flags);
	}

	/**
	 * Execute query
	 *
	 * @return QueryResult
	 */
	public abstract function execute($flags = 0);
	
	/**
	 *
	 * @var numeric
	 */
	const IS_UNION = 0x1;

	/**
	 * Indicates if query contains a UNION statement
	 *
	 * @param unknown $a_ptions
	 * @return boolean
	 */
	public static function isUnion($a_ptions)
	{
		return $a_ptions & self::IS_UNION;
	}

	/**
	 *
	 * @var Datasource
	 */
	private $datasource;
}

/**
 * A basic, data source type independant query interface
 */
class FormattedQuery extends IQuery implements ns\IExpression
{

	/**
	 * Constructor
	 *
	 * @param Datasource $a_datasource
	 * @param string $a_strQuery SQL Query
	 */
	public function __construct(Datasource $a_datasource, $a_strQuery = null)
	{
		parent::__construct($a_datasource);
		$this->set($a_strQuery);
	}

	public function __toString()
	{
		return $this->expressionString();
	}

	public function execute($flags = 0)
	{
		if (!$this->queryString)
		{
			return ns\Reporter::addError($this, __METHOD__ . '(): Null query', __FILE__, __LINE__);
		}
		
		$resultClassName = QueryResult::queryResultClassName($this->queryString);
		$result = $this->datasource->executeQuery($this->expressionString());
		if ($result)
		{
			return new $resultClassName($this->datasource, $result);
		}
		return false;
	}

	public function expressionString($a_ptions = null)
	{
		return $this->queryString;
	}

	public function set($queryString)
	{
		$this->queryString = $queryString;
	}

	protected $queryString = null;
}

/**
 * A query based on a particular table
 */
abstract class TableQuery extends IQuery
{

	/**
	 * Constructor
	 *
	 * @param Datasource $a_datasource
	 * @param mixed $a_table
	 */
	protected function __construct(Table $a_table)
	{
		parent::__construct($a_table->datasource);
		$this->table = $a_table;
	}

	public function __get($key)
	{
		if ($key == 'table')
			return $this->table;
		
		return parent::__get($key);
	}

	/**
	 *
	 * @var Table
	 */
	protected $table;
}
