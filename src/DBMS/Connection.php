<?php

// NAmespace
namespace NoreSources\SQL;

// Aliases
use NoreSources as ns;
use NoreSources\SQL\Constants as K;

class ConnectionException extends \ErrorException
{

	/**
	 *
	 * @param Connection $connection
	 *        	Connection object which raise the exception
	 * @param string $message
	 *        	Error message
	 * @param integer $code
	 *        	Error code
	 */
	public function __construct(Connection $connection, $message, $code = null)
	{
		parent::__construct($message, $code);
		$this->connection = $connection;
	}

	/**
	 *
	 * @return \NoreSources\SQL\Connection
	 */
	public function getConnection()
	{
		return $this->connection;
	}

	/**
	 *
	 * @var Connection
	 */
	private $connection;
}

/**
 * DMBS connection
 */
interface Connection
{

	/**
	 * Begin SQL transaction
	 */
	function beginTransation();

	/**
	 * Commit SQL transation
	 */
	function commitTransation();

	/**
	 * Rollback SQL transaction
	 */
	function rollbackTransaction();

	/**
	 * Connect to DBMS
	 *
	 * @param \ArrayAccess $parameters
	 *        	Connection parameters
	 */
	function connect($parameters);

	/**
	 * Disconnect to DBMS
	 */
	function disconnect();

	/**
	 *
	 * @return StatementBuilder
	 */
	function getStatementBuilder();

	/**
	 *
	 * @param StatementData|string $statement
	 * @return PreparedStatement
	 */
	function prepareStatement($statement);

	/**
	 *
	 * @param PreparedStatement|string $statement
	 * @param ParameterArray $parameters
	 * @return Recordset|integer|boolean
	 */
	function executeStatement($statement, ParameterArray $parameters = null);
}

class ConnectionHelper
{

	/**
	 *
	 * @param array|\ArrayObject $settings
	 *        	Connection settings
	 * @throws ConnectionException
	 * @return \NoreSources\SQL\SQLite\Connection
	 */
	public static function createConnection($settings)
	{
		if (!ns\Container::isArray($settings))
			$settings = [
				K::CONNECTION_PARAMETER_TYPE => $settings
			];

		$type = ns\Container::keyValue($settings, K::CONNECTION_PARAMETER_TYPE, 'Reference');
		$connection = null;
		$className = null;

		$classNames = [
			$type,
			__NAMESPACE__ . '\\' . $type . '\\Connection'
		];

		if (self::$connectionClassMap->offsetExists($type))
		{
			array_unshift($classNames, self::$connectionClassMap->offsetGet($type));
		}

		foreach ($classNames as $className)
		{
			if (class_exists($className) && \is_subclass_of($className, Connection::class, true))
			{
				$cls = new \ReflectionClass($className);
				$connection = $cls->newInstance();
				break;
			}
		}

		if ($connection instanceof Connection)
		{
			$connection->connect($settings);
		}
		else
		{
			throw new ConnectionException(
				'Unable to create a Connection with settings ' . var_export($settings, true));
		}

		return $connection;
	}

	/**
	 *
	 * @param Connection $connection
	 * @param Statement $statement
	 * @param StructureElement $reference
	 * @return PreparedStatement
	 */
	public static function prepareStatement(Connection $connection, Statement $statement,
		StructureElement $reference = null)
	{
		$builder = $connection->getStatementBuilder();
		$context = new StatementContext($builder);
		if ($reference instanceof StructureElement)
			$context->setPivot($reference);
		$stream = new TokenStream();
		$statement->tokenize($stream, $context);
		$data = $builder->finalize($stream);
		$prepared = $connection->prepareStatement($data);
		$prepared->setParameters($data->parameters);
		return $prepared;
	}

	public static function registerConnectionClass($type, $className)
	{
		self::$connectionClassMap->offsetSet($type, $className);
	}

	public static function initialize()
	{
		if (!(self::$connectionClassMap instanceof \ArrayObject))
			self::$connectionClassMap = new \ArrayObject();
	}

	/**
	 *
	 * @var \ArrayObject
	 */
	private static $connectionClassMap;
}

ConnectionHelper::initialize();