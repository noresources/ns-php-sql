<?php
namespace NoreSources\SQL\DBMS\PDO;

use NoreSources\SQL\DBMS\ConnectionInterface;
use NoreSources\SQL\DBMS\ConnectionProviderInterface;
use NoreSources\SQL\DBMS\IdentifierSerializerInterface;
use NoreSources\SQL\DBMS\PlatformInterface;
use NoreSources\SQL\Syntax\MetaFunctionCall;
use NoreSources\SQL\Syntax\Statement\ParameterData;
use Psr\Log\LoggerInterface;

class PDOPlatform implements PlatformInterface,
	ConnectionProviderInterface

{

	public function __construct(ConnectionInterface $connection,
		PlatformInterface $basePlatform)
	{
		$this->basePlatform = $basePlatform;
	}

	public function newStatement($statementType)
	{
		return \call_user_func_array(
			[
				$this->basePlatform,
				'newStatement'
			], func_get_args());
	}

	public function getConnection()
	{
		return $this->basePlatform->getConnection();
	}

	public function quoteStringValue($value)
	{
		$this->connection->quoteStringValue($value);
	}

	public function quoteBinaryData($value)
	{
		$this->connection->quoteBinaryData($value);
	}

	public function quoteIdentifier($identifier)
	{
		if ($this->connection instanceof IdentifierSerializerInterface)
			return $this->connection->quoteIdentifier($identifier);
		return $this->basePlatform->quoteIdentifier($identifier);
	}

	public function quoteIdentifierPath($path)
	{
		return $this->basePlatform->quoteIdentifierPath($path);
	}

	public function literalize($value, $dataType = null)
	{
		return $this->basePlatform->literalize($value, $dataType);
	}

	public function serializeColumnData($columnDescription, $data)
	{
		return $this->basePlatform->serializeColumnData(
			$columnDescription, $data);
	}

	public function serializeData($data, $dataType)
	{
		return $this->basePlatform->serializeData($data, $dataType);
	}

	public function getParameter($name, ParameterData $parameters = null)
	{
		return (':' . $parameters->count());
	}

	public function getKeyword($keyword)
	{
		return $this->basePlatform->getKeyword($keyword);
	}

	public function getJoinOperator($joinTypeFlags)
	{
		return $this->basePlatform->getJoinOperator($joinTypeFlags);
	}

	public function getTimestampFormatTokenTranslation($formatToken)
	{
		return $this->basePlatform->getTimestampFormatTokenTranslation(
			$formatToken);
	}

	public function queryFeature($query, $dflt = null)
	{
		return $this->basePlatform->queryFeature($query, $dflt);
	}

	public function newExpression($baseClassname, ...$arguments)
	{
		return $this->basePlatform->newExpression($baseClassname,
			...$arguments);
	}

	public function translateFunction(MetaFunctionCall $metaFunction)
	{
		return $this->basePlatform->translateFunction($metaFunction);
	}

	public function setLogger(LoggerInterface $logger)
	{
		return $this->basePlatform->setLogger($logger);
	}

	public function getColumnType($columnDescription,
		$constraintFlags = 0)
	{
		return $this->basePlatform->getColumnType($columnDescription,
			$constraintFlags);
	}

	public function getTypeRegistry()
	{
		return $this->basePlatform->getTypeRegistry();
	}

	public function getPlatformVersion($kind = self::VERSION_CURRENT)
	{
		return $this->basePlatform->getPlatformVersion($kind);
	}

	public function getStructureFilenameFactory()
	{
		return $this->basePlatform->getStructureFilenameFactory();
	}

	public function getTimestampTypeStringFormat($type = 0)
	{
		return $this->basePlatform->getTimestampTypeStringFormat($type);
	}

	public function getForeignKeyAction($action)
	{
		return $this->basePlatform->getForeignKeyAction($action);
	}

	/**
	 *
	 * @return \NoreSources\SQL\DBMS\PlatformInterface
	 */
	public function getBasePlatform()
	{
		return $this->basePlatform;
	}

	/**
	 *
	 * @var PDOConnection
	 */
	private $connection;

	/**
	 *
	 * @var PlatformInterface
	 */
	private $basePlatform;
}
