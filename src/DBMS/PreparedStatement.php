<?php
namespace NoreSources\SQL;

/**
 * Pre-built statement
 */
abstract class PreparedStatement implements Statement\InputData, StatementOutputData
{

	use Statement\InputDataTrait;
	use StatementOutputDataTrait;

	/**
	 *
	 * @param
	 *        	string|BuildContext Statement data
	 */
	public function __construct($data)
	{
		if ($data instanceof Statement\InputData)
			$this->initializeInputData($data);
		else
			$this->initializeInputData(null);

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

