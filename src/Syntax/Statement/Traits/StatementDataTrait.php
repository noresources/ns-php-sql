<?php
/**
 * Copyright © 2020 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement\Traits;

use NoreSources\Type\TypeConversion;
use NoreSources\Type\TypeDescription;

/**
 * Reference implementation of StatementDataInterface
 */
trait StatementDataTrait
{
	use StatementInputDataTrait;
	use StatementOutputDataTrait;

	/**
	 *
	 * @param mixed $data
	 *        	Statement data
	 */
	protected function initializeStatementData($data = null)
	{
		$this->initializeParameterData($data);
		$this->initializeOutputData($data);

		$this->sql = '';
		if (TypeDescription::hasStringRepresentation($data))
			$this->sql = TypeConversion::toString($data);
	}

	public function __toString()
	{
		return $this->sql;
	}

	public function setSQL($sql)
	{
		$this->sql = $sql;
	}

	public function getSQL()
	{
		return $this->sql;
	}

	private $sql;
}
