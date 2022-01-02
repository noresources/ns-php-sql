<?php
namespace NoreSources\SQL\DBMS\PDO;

use NoreSources\Container\CascadedValueTree;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\ConnectionInterface;
use NoreSources\SQL\DBMS\ConnectionProviderInterface;
use NoreSources\SQL\DBMS\IdentifierSerializerInterface;
use NoreSources\SQL\DBMS\PlatformInterface;
use NoreSources\SQL\DBMS\Filesystem\StructureFilenameFactoryProviderInterface;
use NoreSources\SQL\DBMS\Traits\ConnectionProviderTrait;
use NoreSources\SQL\Syntax\MetaFunctionCall;
use NoreSources\SQL\Syntax\Statement\ParameterData;
use NoreSources\Type\TypeConversion;
use Psr\Log\LoggerInterface;

class PDOPlatform implements PlatformInterface,
	ConnectionProviderInterface,
	StructureFilenameFactoryProviderInterface

{

	use ConnectionProviderTrait;

	public function __construct(PDOConnection $connection,
		PlatformInterface $basePlatform)
	{
		$this->setConnection($connection);
		$this->basePlatform = $basePlatform;
		$this->pdoFeatures = new CascadedValueTree();
	}

	public function newStatement($statementType)
	{
		return \call_user_func_array(
			[
				$this->basePlatform,
				'newStatement'
			], func_get_args());
	}

	public function getStructureFilenameFactory()
	{
		if ($this->basePlatform instanceof StructureFilenameFactoryProviderInterface)
			return $this->basePlatform->getStructureFilenameFactory();
		return null;
	}

	public function hasStatement($statementType)
	{
		return $this->basePlatform->hasStatement($statementType);
	}

	public function quoteStringValue($value)
	{
		return $this->connection->quoteStringValue(
			TypeConversion::toString($value));
	}

	public function quoteBinaryData($value)
	{
		return $this->connection->quoteBinaryData($value);
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

	public function unserializeColumnData($columnDescription, $data)
	{
		return $this->basePlatform->unserializeColumnData(
			$columnDescription, $data);
	}

	public function getParameter($name,
		$valueDataType = K::DATATYPE_UNDEFINED,
		ParameterData $parameters = null)
	{
		if ($this->queryFeature(K::FEATURE_NAMED_PARAMETERS, false))
			return (':' . $name);
		else
			return '?';
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
		return $this->pdoFeatures->query($query,
			$this->basePlatform->queryFeature($query, $dflt));
	}

	public function newConfigurator(ConnectionInterface $connection)
	{
		return $this->basePlatform->newConfigurator($connection);
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

	public function getTimestampTypeStringFormat($type = 0)
	{
		return $this->basePlatform->getTimestampTypeStringFormat($type);
	}

	public function hasTimestampTypeStringFormat($dataType)
	{
		return $this->basePlatform->hasTimestampTypeStringFormat(
			$dataType);
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

	/**  @var PlatformInterface */
	private $basePlatform;

	/** @var CascadedValueTree */
	private $pdoFeatures;
}
