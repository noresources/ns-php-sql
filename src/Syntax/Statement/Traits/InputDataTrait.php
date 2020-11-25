<?php
/**
 * Copyright © 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement\Traits;

use NoreSources\SQL\Syntax\Statement\ParameterData;
use NoreSources\SQL\Syntax\Statement\StatementInputDataInterface;

/**
 * Implementation of StatementInputDataInterface
 */
trait InputDataTrait
{

	/**
	 *
	 * @return \NoreSources\SQL\Syntax\Statement\ParameterData
	 */
	public function getParameters()
	{
		return $this->parameters;
	}

	public function initializeInputData(
		StatementInputDataInterface $data = null)
	{
		if ($data)
			$this->parameters = $data->getParameters();
		else
			$this->parameters = new ParameterData();
	}

	/**
	 *
	 * @var ParameterData Array of Parameter
	 *      The entry key is the parameter name as it appears in the Statement.
	 */
	private $parameters;
}
