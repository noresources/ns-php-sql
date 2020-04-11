<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS;

use NoreSources\SQL\Statement\InputData;
use NoreSources\SQL\Statement\InputDataTrait;
use NoreSources\SQL\Statement\OutputDataTrait;
use NoreSources\SQL\Statement\StatementData;

/**
 * Reference to a prepared statement
 */
abstract class PreparedStatement extends StatementData
{

	use InputDataTrait;
	use OutputDataTrait;

	/**
	 *
	 * @param
	 *        	string|InputData Statement data
	 */
	public function __construct($data)
	{
		if ($data instanceof InputData)
			$this->initializeInputData($data);
		else
			$this->initializeInputData(null);

		$this->initializeOutputData($data);
	}

	/**
	 *
	 * @retur string SQL statement string
	 */
	public function __toString()
	{
		return $this->getStatement();
	}

	/**
	 *
	 * @return string SQL statement string
	 */
	abstract function getStatement();
}

