<?php
/**
 * Copyright © 2020 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement\Traits;

use NoreSources\SQL\Syntax\Statement\Statement;

/**
 * Implements StatementTypeProviderInterface
 */
trait StatementTypeProviderTrait
{

	/**
	 *
	 * @return integer
	 */
	public function getStatementType()
	{
		return $this->statementType;
	}

	protected function initializeStatementType($data = null)
	{
		$this->statementType = Statement::statementTypeFromData($data);
	}

	/**
	 *
	 * @var integer
	 */
	private $statementType;
}
