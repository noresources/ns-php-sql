<?php

// Namespace
namespace NoreSources\SQL;

// Aliases
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Expression as X;
use NoreSources\Expression as xpr;
use NoreSources as ns;

interface StatementInputData
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
	 * @return StatementParameterMap
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

	function initializeStatementInputData(StatementInputData $data = null);
}

/**
 * Implementation of StatementInputData
 */
trait StatementInputDataTrait
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

	public function initializeStatementInputData(StatementInputData $data = null)
	{
		if ($data)
			$this->parameters = $data->getParameters();
		else
			$this->parameters = new StatementParameterMap();
	}

	/**
	 *
	 * @var StatementParameterMap Array of StatementParameter
	 *      The entry key is the parameter name as it appears in the Statement.
	 */
	private $parameters;
}
