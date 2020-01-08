<?php
/**
 * Copyright © 2012-2018 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS;

// Aliases
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Expression\TokenStream;
use NoreSources\SQL\Statement\BuildContext;
use NoreSources\SQL\Statement\Statement;
use NoreSources\SQL\Structure\StructureElement;
use NoreSources as ns;

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
			if (\class_exists($className) && \is_subclass_of($className, Connection::class, true))
			{
				$cls = new \ReflectionClass($className);
				$connection = $cls->newInstance();
				break;
			}
		}

		if ($connection instanceof Connection)
			$connection->connect($settings);
		else
			throw new ConnectionException(null,
				'Unable to create a Connection using classes ' . implode(', ', $classNames));

		return $connection;
	}

	/**
	 * Get the DBMS-specific SQL string representation of the given statement
	 *
	 * @param Connection $connection
	 *        	DBMS connection
	 * @param Statement $statement
	 *        	Statement to convert to string
	 * @param StructureElement $reference
	 *        	Pivot StructureElement
	 * @return string SQL string in the DBMS dialect
	 *        
	 * @note This method does not provide any informations about statement parameters or result column types.
	 * Tf these information are needed, use ConnectionHelper::prepareStatement()
	 */
	public static function getStatementSQL($connection, Statement $statement,
		StructureElement $reference = null)
	{
		$reference = ($reference instanceof StructureElement) ? $reference : $connection->getStructure();
		$builder = $connection->getStatementBuilder();
		$context = new BuildContext($builder);
		if ($reference instanceof StructureElement)
			$context->setPivot($reference);
		$stream = new TokenStream();
		$statement->tokenize($stream, $context);
		$builder->finalize($stream, $context);
		return strval($context);
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
		$reference = ($reference instanceof StructureElement) ? $reference : $connection->getStructure();
		$builder = $connection->getStatementBuilder();
		$context = new BuildContext($builder);
		if ($reference instanceof StructureElement)
			$context->setPivot($reference);
		$stream = new TokenStream();
		$statement->tokenize($stream, $context);
		$builder->finalize($stream, $context);
		$prepared = $connection->prepareStatement($context);
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