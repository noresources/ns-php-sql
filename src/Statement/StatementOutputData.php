<?php

// Namespace
namespace NoreSources\SQL;

// Aliases
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Expression as X;
use NoreSources\Expression as xpr;
use NoreSources as ns;

interface StatementOutputData
{

	/**
	 *
	 * @return integer
	 */
	function getStatementType();

	/**
	 *
	 * @return integer
	 */
	function getResultColumnCount();

	/**
	 *
	 * @param string $key
	 * @return ResultColumn
	 */
	function getResultColumn($key);

	/**
	 *
	 * @return ResultColumnMap
	 */
	function getResultColumns();

	/**
	 *
	 * @return \ArrayIterator
	 */
	function getResultColumnIterator();

	/**
	 *
	 * @param StatementOutputData $data
	 */
	function initializeStatementOutputData($data = null);
}

/**
 * Implementation of StatementOutputData
 */
trait StatementOutputDataTrait
{

	public function getStatementType()
	{
		return $this->statementType;
	}

	/**
	 *
	 * @return number
	 */
	public function getResultColumnCount()
	{
		return $this->resultColumns->count();
	}

	public function getResultColumn($key)
	{
		return $this->resultColumns->getColumn($key);
	}

	public function getResultColumns()
	{
		return $this->resultColumns;
	}

	public function getResultColumnIterator()
	{
		return $this->resultColumns->getIterator();
	}

	public function initializeStatementOutputData($data = null)
	{
		if ($data instanceof StatementOutputData)
		{
			$this->statementType = $data->getStatementType();
			$this->resultColumns = $data->getResultColumns();
		}
		else
		{
			$this->statementType = Statement::statementTypeFromData($data);
			$this->resultColumns = new ResultColumnMap();
		}
	}

	/**
	 *
	 * @var integer
	 */
	protected $statementType;

	/**
	 *
	 * @var ResultColumnMap
	 */
	private $resultColumns;
}
