<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\QueryResult;

class GenericInsertionQueryResult implements InsertionQueryResultInterface
{

	public function __construct($insretId)
	{
		$this->insertId;
	}

	public function getInsertId()
	{
		return $this->insertId;
	}

	private $insertId;
}