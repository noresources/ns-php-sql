<?php
namespace NoreSources\SQL\Statement;

use NoreSources\TypeConversion;
use NoreSources\TypeDescription;

/**
 * Reference implementation of StatementDataInterface
 */
trait StatementDataTrait
{
	use InputDataTrait;
	use OutputDataTrait;

	/**
	 *
	 * @param mixed $data
	 *        	Statement data
	 */
	protected function initializeStatementData($data)
	{
		if ($data instanceof StatementInputDataInterface)
			$this->initializeInputData($data);
		else
			$this->initializeInputData(null);
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
