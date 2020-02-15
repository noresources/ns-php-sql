<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */

// Namespace
namespace NoreSources\SQL\Statement;

/**
 * Implementation of Statement\OutputData
 */
trait OutputDataTrait
{

	public function getStatementType()
	{
		return $this->statementType;
	}

	public function getResultColumns()
	{
		return $this->resultColumns;
	}

	public function initializeOutputData($data = null)
	{
		if ($data instanceof OutputData)
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
