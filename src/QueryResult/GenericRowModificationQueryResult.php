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

class GenericRowModificationQueryResult implements RowModificationQueryResultInterface, \Countable
{

	public function __construct($c)
	{
		$this->affectedRowCount = $c;
	}

	public function getAffectedRowCount()
	{
		return $this->affectedRowCount;
	}

	public function count()
	{
		return $this->affectedRowCount;
	}

	private $affectedRowCount;
}
