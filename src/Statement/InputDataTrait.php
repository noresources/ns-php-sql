<?php
/**
 * Copyright © 2012-2018 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */

// Namespace
namespace NoreSources\SQL\Statement;

/**
 * Implementation of InputData
 */
trait InputDataTrait
{

	public function getNamedParameterCount()
	{
		return $this->parameters->getNamedParameterCount();
	}

	public function getParameterCount()
	{
		return $this->parameters->count();
	}

	public function hasParameter($key)
	{
		return $this->parameters->offsetExists($key);
	}

	public function getParameter($key)
	{
		return $this->parameters->offsetGet($key);
	}

	public function getParameters()
	{
		return $this->parameters;
	}

	public function registerParameter($position, $key, $dbmsName)
	{
		$this->parameters->offsetSet(intval($position), $dbmsName);
		$this->parameters->offsetSet(strval($key), $dbmsName);
	}

	public function initializeInputData(InputData $data = null)
	{
		if ($data)
			$this->parameters = $data->getParameters();
		else
			$this->parameters = new ParameterMap();
	}

	/**
	 *
	 * @var ParameterMap Array of Parameter
	 *      The entry key is the parameter name as it appears in the Statement.
	 */
	private $parameters;
}
