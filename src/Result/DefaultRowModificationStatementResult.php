<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Result;

class DefaultRowModificationStatementResult implements
	RowModificationStatementResultInterface, \Countable
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
