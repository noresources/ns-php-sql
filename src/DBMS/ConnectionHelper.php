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

// Aliases
use NoreSources\Container;
use NoreSources\TypeConversion;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\ParameterValue;
use NoreSources\SQL\Expression\TokenStream;
use NoreSources\SQL\Statement\BuildContext;
use NoreSources\SQL\Statement\Statement;
use NoreSources\SQL\Structure\StructureElement;

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

	/**
	 * Get the DBMS-specific SQL string representation of the given statement
	 *
	 * @param ConnectionInterface $connection
	 *        	DBMS connection
	 * @param Statement $statement
	 *        	Statement to convert to string
	 * @param StructureElement $reference
	 *        	Pivot StructureElement
	 * @return object SQL string representation and additional informations
	 *
	 * @note This method does not provide any informations about statement parameters or result column types.
	 * Tf these information are needed, use ConnectionHelper::prepareStatement()
	 */
	public static function getStatementData($connection, Statement $statement,
		StructureElement $reference = null)
	{
		$reference = ($reference instanceof StructureElement) ? $reference : $connection->getStructure();
		$builder = $connection->getStatementBuilder();
		$context = new BuildContext($builder);
		if ($reference instanceof StructureElement)
			$context->setPivot($reference);
		$stream = new TokenStream();
		$statement->tokenize($stream, $context);
		return $builder->finalizeStatement($stream, $context);
	}

	/**
	 *
	 * @param ConnectionInterface $connection
	 * @param Statement $statement
	 * @param StructureElement $reference
	 * @return PreparedStatement
	 */
	public static function prepareStatement(ConnectionInterface $connection, Statement $statement,
		StructureElement $reference = null)
	{
		$reference = ($reference instanceof StructureElement) ? $reference : $connection->getStructure();
		$builder = $connection->getStatementBuilder();
		$context = new BuildContext($builder);
		if ($reference instanceof StructureElement)
			$context->setPivot($reference);
		$stream = new TokenStream();
		$statement->tokenize($stream, $context);
		$result = $builder->finalizeStatement($stream, $context);
		$prepared = $connection->prepareStatement($result);
		return $prepared;
	}

	public static function registerConnectionClass($type, $className)
	{
		self::$connectionClassMap->offsetSet($type, $className);
	}

	public static function serializeParameterValue(ConnectionInterface $connection,
		ParameterValue $parameterValue)
	{
		if ($parameterValue->type & K::DATATYPE_NUMBER)
		{
			if ($parameterValue->type == K::DATATYPE_INTEGER)
				return TypeConversion::toInteger($parameterValue->value);
			else
				return TypeConversion::toFloat($parameterValue->value);
		}
		elseif ($parameterValue->type == K::DATATYPE_BOOLEAN)
			return TypeConversion::toBoolean($parameterValue->value);
		elseif ($parameterValue->type == K::DATATYPE_NULL)
			return null;
		elseif ($parameterValue->value instanceof \DateTimeInterface)
		{
			$f = $connection->getStatementBuilder()->getTimestampFormat(K::DATATYPE_TIMESTAMP);
			if ($parameterValue->type & K::DATATYPE_TIMESTAMP)
				$f = $connection->getStatementBuilder()->getTimestampFormat($parameterValue->type);

			return $parameterValue->value->format($f);
		}

		return $parameterValue->value;
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