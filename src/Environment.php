<?php
namespace NoreSources\SQL;

use NoreSources\SQL\DBMS\ConnectionFactoryStack;
use NoreSources\SQL\DBMS\ConnectionInterface;
use NoreSources\SQL\DBMS\ConnectionProviderInterface;
use NoreSources\SQL\DBMS\DefaultConnectionFactory;
use NoreSources\SQL\DBMS\PlatformProviderInterface;
use NoreSources\SQL\Expression\TokenizableExpressionInterface;
use NoreSources\SQL\Result\StatementResultInterface;
use NoreSources\SQL\Statement\Statement;
use NoreSources\SQL\Statement\StatementBuilder;
use NoreSources\SQL\Statement\StatementFactoryInterface;
use NoreSources\SQL\Structure\StructureElementInterface;
use NoreSources\SQL\Structure\StructureProviderInterface;
use NoreSources\SQL\Structure\StructureResolver;
use NoreSources\SQL\Structure\StructureSerializerFactory;
use NoreSources\SQL\Structure\VirtualStructureResolver;

/**
 * SQL environment container
 *
 * A SQL environment is a combination of
 * <ul>
 * <li>A DBMS connection</li>
 * <li>A SQL structure definition</li>
 * <li>A conneciton factory stack (static)</li>
 * </ul>
 */
class Environment implements ConnectionProviderInterface,
	PlatformProviderInterface, StatementFactoryInterface,
	StructureProviderInterface
{

	/**
	 *
	 * @param string|Statement $statement
	 * @param StructureElementInterface $reference
	 * @return \NoreSources\SQL\DBMS\PreparedStatement
	 */
	public function prepareStatement($statement,
		StructureElementInterface $reference = null)
	{
		if ($statement instanceof TokenizableExpressionInterface)
		{
			if ($reference === null)
				$reference = $this->getStructure();
			$resolver = ($reference ? new StructureResolver($reference) : new VirtualStructureResolver());
			$builder = StatementBuilder::getInstance();
			$statement = $builder($statement, $this->getPlatform(),
				$resolver);
		}

		return $this->connection->prepareStatement($statement);
	}

	/**
	 *
	 * @param string|Statement $statement
	 * @param array $parameters
	 * @return StatementResultInterface|boolean
	 */
	public function executeStatement($statement, $parameters = null)
	{
		if ($statement instanceof TokenizableExpressionInterface)
			$statement = $this->prepareStatement($statement,
				$this->getStructure());

		return $this->connection->executeStatement($statement,
			$parameters);
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\DBMS\ConnectionProviderInterface::getConnection()
	 */
	public function getConnection()
	{
		return $this->connection;
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\DBMS\PlatformProviderInterface::getPlatform()
	 */
	public function getPlatform()
	{
		return $this->connection->getPlatform();
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\Statement\StatementFactoryInterface::newStatement()
	 */
	public function newStatement($statementType)
	{
		return \call_user_func_array(
			[
				$this->connection->getPlatform(),
				'newStatement'
			], func_get_args());
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\Structure\StructureProviderInterface::getStructure()
	 */
	public function getStructure()
	{
		return $this->structure;
	}

	/**
	 *
	 * @return \NoreSources\SQL\DBMS\ConnectionFactoryStack
	 */
	public static function getConnectionFactoryStack()
	{
		if (!isset(self::$connectionFactoryStack))
		{
			self::$connectionFactoryStack = new ConnectionFactoryStack();
			self::$connectionFactoryStack->pushFactory(
				new DefaultConnectionFactory());
		}
		return self::$connectionFactoryStack;
	}

	public function __construct()
	{
		$arguments = func_get_args();
		$connectionSettings = null;
		foreach ($arguments as $argument)
		{
			if ($argument instanceof StructureElementInterface)
				$this->structure = $argument;
			elseif ($argument instanceof ConnectionInterface)
				$this->connection = $argument;
			elseif (\is_file($argument))
			{
				$factory = new StructureSerializerFactory();
				$this->structure = $factory->structureFromFile(
					$argument);
			}
			elseif ($connectionSettings === null)
				$connectionSettings = $argument;
		}

		if (!isset($this->connection))
		{
			$this->connection = self::getConnectionFactoryStack()->createConnection(
				(($connectionSettings !== null) ? $connectionSettings : []));
		}
	}

	/**
	 *
	 * @var ConnectionInterface
	 */
	private $connection;

	/**
	 *
	 * @var StructureElementInterface
	 */
	private $structure;

	/**
	 *
	 * @var ConnectionFactoryStack
	 */
	private static $connectionFactoryStack;
}