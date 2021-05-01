<?php
/**
 * Copyright © 2020 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement\Traits;

use NoreSources\SQL\Syntax\Statement\ResultColumnProviderInterface;
use NoreSources\SQL\Syntax\Statement\StatementTypeProviderInterface;

/**
 * Implementation of StatementTypeProviderInterface, ResultColumnProviderInterface
 */
trait StatementOutputDataTrait
{

	use StatementTypeProviderTrait;
	use ResultColumnProviderTrait;

	/**
	 *
	 * @param StatementTypeProviderInterface|ResultColumnProviderInterface $data
	 */
	protected function initializeOutputData($data = null)
	{
		$this->initializeStatementType($data);
		$this->initializeResultColumnData($data);
	}
}
