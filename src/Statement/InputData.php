<?php

// Namespace
namespace NoreSources\SQL\Statement;

interface InputData
{

	/**
	 *
	 * @return integer
	 */
	function getNamedParameterCount();

	/**
	 *
	 * @return integer Total number of parameter occurences
	 */
	function getParameterCount();

	/**
	 *
	 * @param integer|string $key
	 *        	Parameter position or name
	 * @return boolean
	 */
	function hasParameter($key);

	/**
	 *
	 * @param integer|string $key
	 *        	Parameter name or index
	 * @return string DBMS representation of the parameter name
	 */
	function getParameter($key);

	/**
	 *
	 * @return ParameterMap
	 */
	function getParameters();

	/**
	 *
	 * @param integer $position
	 *        	Parameter position in the statement
	 * @param string $key
	 *        	Parameter name
	 * @param string $dbmsName
	 *        	DBMS representation of the parameter name
	 */
	function registerParameter($position, $key, $dbmsName);

	function initializeInputData(InputData $data = null);
}

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
