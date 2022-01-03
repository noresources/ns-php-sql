<?php
/**
 * Copyright © 2020 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement\Traits;

use NoreSources\SQL\Syntax\Statement\ResultColumnMap;
use NoreSources\SQL\Syntax\Statement\ResultColumnProviderInterface;

/**
 * Implements ResultColumnProviderInterface
 */
trait ResultColumnProviderTrait
{

	/**
	 *
	 * @return ResultColumnMap
	 */
	public function getResultColumns()
	{
		return $this->resultColumns;
	}

	protected function initializeResultColumnData($data = null)
	{
		if ($data instanceof ResultColumnProviderInterface)
			$data = $data->getResultColumns();

		if ($data instanceof ResultColumnMap)
			$this->resultColumns = $data;
		else
			$this->resultColumns = new ResultColumnMap();
	}

	/**
	 *
	 * @var ResultColumnMap
	 */
	private $resultColumns;
}
