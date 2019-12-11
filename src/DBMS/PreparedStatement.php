<?php
namespace NoreSources\SQL;

use NoreSources as ns;
use NoreSources\SQL\Constants as K;

/**
 * Pre-built statement
 */
abstract class PreparedStatement implements StatementInputData, StatementOutputData
{

	use StatementInputDataTrait;
	use StatementOutputDataTrait;

	/**
	 *
	 * @param
	 *        	string|BuildContext Statement data
	 */
	public function __construct($data)
	{
		if ($data instanceof StatementInputData)
			$this->initializeStatementInputData($data);
		else
			$this->initializeStatementInputData(null);

		$this->initializeStatementOutputData($data);
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

