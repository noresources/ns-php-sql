<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS;

use NoreSources\Container;
use NoreSources\TypeConversion;
use NoreSources\TypeDescription;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\ParameterValue;
use NoreSources\SQL\Expression\Literal;
use NoreSources\SQL\Expression\StructureElementIdentifier;
use NoreSources\SQL\Expression\TokenStream;
use NoreSources\SQL\Statement\Statement;
use NoreSources\SQL\Statement\StatementData;
use NoreSources\SQL\Statement\StatementTokenStreamContext;
use NoreSources\SQL\Structure\IndexStructure;
use NoreSources\SQL\Structure\NamespaceStructure;
use NoreSources\SQL\Structure\StructureElementContainerInterface;
use NoreSources\SQL\Structure\StructureElementInterface;
use NoreSources\SQL\Structure\TableStructure;

/**
 * Helper method for creation of Connection, statement and prepared statement
 */
class ConnectionHelper
{

	/**
	 *
	 * @param array|\ArrayObject $settings
	 *        	ConnectionInterface settings
	 * @throws ConnectionException
	 * @return \NoreSources\SQL\SQLite\ConnectionInterface
	 */
	public static function createConnection($settings)
	{
		if (!Container::isArray($settings))
			$settings = [
				K::CONNECTION_TYPE => $settings
			];

		$type = Container::keyValue($settings, K::CONNECTION_TYPE, 'Reference');
		$connection = null;
		$className = null;

		$classNames = [
			$type,
			__NAMESPACE__ . '\\' . $type . '\\Connection',
			__NAMESPACE__ . '\\' . $type . '\\' . $type . 'Connection'
		];

		if (self::$connectionClassMap->offsetExists($type))
		{
			array_unshift($classNames, self::$connectionClassMap->offsetGet($type));
		}

		foreach ($classNames as $className)
		{
			if (\class_exists($className) &&
				\is_subclass_of($className, ConnectionInterface::class, true))
			{
				$cls = new \ReflectionClass($className);
				$connection = $cls->newInstance();
				break;
			}
		}

		if ($connection instanceof ConnectionInterface)
			$connection->connect($settings);
		else
			throw new ConnectionException(null,
				'Unable to create a ConnectionInterface using classes ' . implode(', ', $classNames));

		return $connection;
	}

	public static function createStructure(ConnectionInterface $connection,
		StructureElementInterface $structure = null)
	{
		if (!($structure instanceof StructureElementIdentifier))
			$structure = $connection->getStructure();

		if (!($structure instanceof StructureElementInterface))
			throw new \Exception('No structure');

		if ($structure instanceof NamespaceStructure)
		{
		/**
		 *
		 * @todo Create schema
		 */
		}
		elseif ($structure instanceof TableStructure)
		{
			$q = $connection->getStatementFactory()->newStatement(K::QUERY_CREATE_TABLE, $structure);
			$statement = self::buildStatement($connection, $statement, $structure);
			return $connection->executeStatement($statement);
		}
		elseif ($structure instanceof IndexStructure)
		{
			/**
			 *
			 * @var \NoreSources\SQL\Statement\Structure\CreateIndexQuery $q
			 */
			$q = $connection->getStatementFactory()->newStatement(K::QUERY_CREATE_INDEX);
			$q->setFromIndexStructure($structure);
			$statement = self::buildStatement($connection, $statement, $structure);
			return $connection->executeStatement($statement);
		}

		if ($structure instanceof StructureElementContainerInterface)
		{
			foreach ($structure as $e)
			{
				self::createStructure($connection, $e);
			}
		}
	}

	/**
	 * Get the DBMS-specific SQL string representation of the given statement
	 *
	 * @param ConnectionInterface $connection
	 *        	DBMS connection
	 * @param Statement $statement
	 *        	Statement to convert to string
	 * @param StructureElementInterface $reference
	 *        	Pivot StructureElement
	 * @return object SQL string representation and additional informations
	 *
	 * @note This method does not provide any informations about statement parameters or result column types.
	 * Tf these information are needed, use ConnectionHelper::prepareStatement()
	 */
	public static function buildStatement($connection, Statement $statement,
		StructureElementInterface $reference = null)
	{
		$reference = ($reference instanceof StructureElementInterface) ? $reference : $connection->getStructure();
		$builder = $connection->getStatementBuilder();
		$context = new StatementTokenStreamContext($builder);
		if ($reference instanceof StructureElementInterface)
			$context->setPivot($reference);
		$stream = new TokenStream();
		$statement->tokenize($stream, $context);
		return $builder->finalizeStatement($stream, $context);
	}

	/**
	 *
	 * @param ConnectionInterface $connection
	 * @param Statement|StatementDataInterface $statement
	 * @param StructureElementInterface $reference
	 * @return PreparedStatement
	 */
	public static function prepareStatement(ConnectionInterface $connection, $statement,
		StructureElementInterface $reference = null)
	{
		$reference = ($reference instanceof StructureElementInterface) ? $reference : $connection->getStructure();
		$statementData = null;
		if ($statement instanceof StatementData)
		{
			$statementData = $statement;
		}
		elseif ($statement instanceof Statement)
		{
			$builder = $connection->getStatementBuilder();
			$context = new StatementTokenStreamContext($builder);
			if ($reference instanceof StructureElementInterface)
				$context->setPivot($reference);
			$stream = new TokenStream();
			$statement->tokenize($stream, $context);
			$statementData = $builder->finalizeStatement($stream, $context);
		}
		elseif (TypeDescription::hasStringRepresentation($statement))
		{
			$statementData = TypeConversion::toString($statement);
		}
		else
			throw ConnectionException($connection,
				'Unable to prepare statement. ' . Statement::class . ', ' . StatementData::class .
				' or stringifiable type expected. Got ' . TypeDescription::getName($statement));
		$prepared = $connection->prepareStatement($statementData);
		return $prepared;
	}

	public static function registerConnectionClass($type, $className)
	{
		self::$connectionClassMap->offsetSet($type, $className);
	}

	/**
	 *
	 * @param ConnectionInterface $connection
	 * @param ParameterValue|mixed $value
	 *        	Parameter value to serialize
	 * @param integer|NULL $dataType
	 *        	Parameter target type (if $value is not a ParameterValue)
	 * @return number|boolean|NULL|\NoreSources\SQL\ParameterValue|\DateTimeInterface
	 */
	public static function serializeParameterValue(ConnectionInterface $connection, $value,
		$dataType = null)
	{
		$type = (\is_integer($dataType) && $dataType) ? $dataType : K::DATATYPE_UNDEFINED;
		if ($value instanceof ParameterValue)
		{
			$type = $value->type;
			$value = $value;
		}

		if ($type == K::DATATYPE_UNDEFINED)
			$type = Literal::dataTypeFromValue($value);

		if ($type & K::DATATYPE_NUMBER)
		{
			if ($type == K::DATATYPE_INTEGER)
				return TypeConversion::toInteger($value);
			return TypeConversion::toFloat($value);
		}
		elseif ($type == K::DATATYPE_BOOLEAN)
			return TypeConversion::toBoolean($value);
		elseif ($type == K::DATATYPE_NULL)
			return null;
		elseif ($value instanceof \DateTimeInterface)
		{
			return $value->format(
				$connection->getStatementBuilder()
					->getTimestampFormat($type & K::DATATYPE_TIMESTAMP));
		}

		return $value;
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