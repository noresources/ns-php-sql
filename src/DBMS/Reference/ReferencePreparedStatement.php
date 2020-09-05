<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\Reference;

use NoreSources\SQL\DBMS\PreparedStatementInterface;
use NoreSources\SQL\Statement\StatementDataTrait;

/**
 * SQLite3 implementation of NoreSources\SQL\ReferencePreparedStatement
 */
class ReferencePreparedStatement implements PreparedStatementInterface
{

	use StatementDataTrait;

	/**
	 *
	 * @param \NoreSources\SQL\Statement\ParameterDataProviderInterface $data
	 */
	public function __construct($data)
	{
		$this->initializeStatementData($data);
	}
}