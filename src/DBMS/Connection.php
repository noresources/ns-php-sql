<?php

// NAmespace
namespace NoreSources\SQL;

// Aliases
use NoreSources as ns;
use NoreSources\SQL\Constants as K;

/**
 * DMBS connection
 */
interface Connection
{

	/**
	 * Begin SQL transaction
	 */
	function beginTransation();

	/**
	 * Commit SQL transation
	 */
	function commitTransation();

	/**
	 * Rollback SQL transaction
	 */
	function rollbackTransaction();

	/**
	 * Connect to DBMS
	 *
	 * @param \ArrayAccess $parameters
	 *        	Connection parameters
	 */
	function connect($parameters);

	/**
	 * Disconnect to DBMS
	 */
	function disconnect();

	/**
	 *
	 * @return StatementBuilder
	 */
	function getStatementBuilder();

	/**
	 *
	 * @param StatementContext|string $statement
	 * @return PreparedStatement
	 */
	function prepareStatement($statement);

	/**
	 *
	 * @param PreparedStatement|string $statement
	 * @param ParameterArray $parameters
	 * @return Recordset|integer|boolean
	 */
	function executeStatement($statement, ParameterArray $parameters = null);

	/**
	 * Get the structure of the connected database
	 *
	 * @return StructureElement
	 */
	function getStructure();
}

trait ConnectionStructureTrait
{

	public function getStructure()
	{
		return $this->connectionStructure;
	}

	protected function setStructure($structure)
	{
		if ($structure instanceof StructureElement)
			$this->connectionStructure = $structure;
		elseif (is_file($structure))
			$this->connectionStructure = StructureSerializerFactory::structureFromFile($filename);
		else
			throw new \InvalidArgumentException(
				ns\TypeDescription::getName($structure) .
				' is not a valid argument. Instance of StructureElement or filename expected');
	}

	private $connectionStructure;
}

