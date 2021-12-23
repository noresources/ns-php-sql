<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\Reference;

use NoreSources\SQL\DBMS\PreparedStatementInterface;
use NoreSources\SQL\Syntax\Statement\Traits\StatementDataTrait;
use NoreSources\SQL\Syntax\Statement\Traits\StatementSerializationTrait;

/**
 * Reference (dummy) implementation of PreparedStatement
 */
class ReferencePreparedStatement implements PreparedStatementInterface
{

	use StatementDataTrait;
	use StatementSerializationTrait;

	/**
	 *
	 * @param \NoreSources\SQL\Syntax\Statement\ParameterDataProviderInterface $data
	 */
	public function __construct($data)
	{
		$this->initializeStatementData($data);
	}
}