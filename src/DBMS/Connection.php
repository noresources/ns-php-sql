<?php

// NAmespace
namespace NoreSources\SQL\DBMS;

// Aliases
use NoreSources\SQL\StructureElement;
use NoreSources\SQL\StructureSerializerFactory;
use NoreSources as ns;

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
	 * @param BuildContext|string $statement
	 * @return PreparedStatement
	 */
	function prepareStatement($statement);

	/**
	 *
	 * @param PreparedStatement|string $statement
	 * @param StatementParameterArray $parameters
	 * @return Recordset|integer|boolean
	 */
	function executeStatement($statement, StatementParameterArray $parameters = null);

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

