<?php
/**
 * Copyright © 2020 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement\Traits;

use NoreSources\SQL\Syntax\Statement\ParameterData;
use NoreSources\SQL\Syntax\Statement\ParameterDataProviderInterface;

/**
 * Implementation of ParameterDataProviderInterface
 */
trait StatementInputDataTrait
{

	/**
	 *
	 * @return \NoreSources\SQL\Syntax\Statement\ParameterData
	 */
	public function getParameters()
	{
		return $this->parameters;
	}

	/**
	 *
	 * @param ParameterDataProviderInterface $data
	 */
	protected function initializeParameterData($data = null)
	{
		if ($data instanceof ParameterDataProviderInterface)
			$data = $data->getParameters();

		if ($data instanceof ParameterData)
			$this->parameters = $data;
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
