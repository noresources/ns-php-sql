<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\Statement;

/**
 * Reference implementation of StatementDataInterface
 */
class StatementData implements StatementDataInterface
{
	use InputDataTrait;
	use OutputDataTrait;

	public function __construct($data)
	{
		$this->initializeInputData($data);
		$this->initializeOutputData($data);
		$this->sql = '';
	}

	public function __toString()
	{
		return $this->sql;
	}

	public function setSQL($sql)
	{
		$this->sql = $sql;
	}

	private $sql;
}