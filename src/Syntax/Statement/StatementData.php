<?php
/**
 * Copyright © 2020 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement;

use NoreSources\SQL\Syntax\Statement\Traits\StatementDataTrait;
use NoreSources\SQL\Syntax\Statement\Traits\StatementSerializationTrait;

/**
 * Reference implementation of StatementDataInterface
 */
class StatementData implements StatementDataInterface
{
	use StatementDataTrait;
	use StatementSerializationTrait;

	public function __construct($data)
	{
		$this->initializeStatementData($data);
	}
}