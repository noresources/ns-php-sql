<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\Result;

/**
 * SeekableRecordsetTrait should be used on Recordset implementing SeekableIterator.
 *
 * The trait expect a seekRecord ($position) : bool
 */
trait SeekableRecordsetTrait
{

	/**
	 *
	 * @param integer $position
	 * @throws RecordsetException
	 */
	public function seek($position)
	{
		$result = $this->seekRecord($position);
		if (!$result)
			throw new RecordsetException($this, 'Failed to seek to ' . $position);

		$this->rowIndex = $position;
		$this->internalRecord = $this->fetch($this->rowIndex);

		if ($this->internalRecord)
			$this->setIteratorPosition($this->rowIndex, 0);
		else
			$this->setIteratorPosition(-1, self::POSITION_END);

		$this->updateRecord();
	}
}