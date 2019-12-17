<?php
namespace NoreSources\SQL\DBMS;

use NoreSources\SQL\Statement\InputData;
use NoreSources\SQL\Statement\InputDataTrait;
use NoreSources\SQL\Statement\OutputData;
use NoreSources\SQL\Statement\OutputDataTrait;

/**
 * Pre-built statement
 */
abstract class PreparedStatement implements InputData, OutputData
{

	use InputDataTrait;
	use OutputDataTrait;

	/**
	 *
	 * @param
	 *        	string|BuildContext Statement data
	 */
	public function __construct($data)
	{
		if ($data instanceof InputData)
			$this->initializeInputData($data);
		else
			$this->initializeInputData(null);

		$this->initializeOutputData($data);
	}

	/**
	 *
	 * @retur string SQL statement string
	 */
	public function __toString()
	{
		return $this->getStatement();
	}

	/**
	 *
	 * @return string SQL statement string
	 */
	abstract function getStatement();
}

