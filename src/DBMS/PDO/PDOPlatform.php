<?php
namespace NoreSources\SQL\DBMS\PDO;

use NoreSources\SQL\DBMS\ConnectionInterface;
use NoreSources\SQL\DBMS\ConnectionProviderInterface;
use NoreSources\SQL\DBMS\IdentifierSerializerInterface;
use NoreSources\SQL\DBMS\PlatformInterface;
use NoreSources\SQL\Expression\MetaFunctionCall;
use NoreSources\SQL\Statement\ParameterData;
use NoreSources\SQL\Structure\ColumnDescriptionInterface;
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

	public function serializeColumnData(
		ColumnDescriptionInterface $column, $data)
	{
		return $this->basePlatform->serializeColumnData($column, $data);
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

	public function translateFunction(MetaFunctionCall $metaFunction)
	{
		return $this->basePlatform->translateFunction($metaFunction);
	}

	public function setLogger(LoggerInterface $logger)
	{
		return $this->basePlatform->setLogger($logger);
	}

	public function getColumnType(ColumnDescriptionInterface $column,
		$constraintFlags = 0)
	{
		return $this->basePlatform->getColumnType($column,
			$constraintFlags);
	}

	public function getPlatformVersion($kind = self::VERSION_CURRENT)
	{
		return $this->basePlatform->getPlatformVersion($kind);
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
	 * @var PDOConnection
	 */
	private $connection;

	/**
	 *
	 * @var PlatformInterface
	 */
	private $basePlatform;
}
