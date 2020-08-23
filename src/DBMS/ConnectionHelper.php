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
use NoreSources\SQL\Expression\TokenStream;
use NoreSources\SQL\Statement\Statement;
use NoreSources\SQL\Statement\StatementData;
use NoreSources\SQL\Statement\StatementDataInterface;
use NoreSources\SQL\Statement\StatementTokenStreamContext;
use NoreSources\SQL\Structure\DatasourceStructure;
use NoreSources\SQL\Structure\IndexStructure;
use NoreSources\SQL\Structure\NamespaceStructure;
use NoreSources\SQL\Structure\StructureElementInterface;
use NoreSources\SQL\Structure\StructureSerializerFactory;
use NoreSources\SQL\Structure\TableStructure;

/**
 * Helper method for creation of Connection, statement and prepared statement
 */
class ConnectionHelper
{

	/**
	 *
	 * @param array $settings
	 *        	Connection settings or connection type.$this
	 *        	CONNECTION_STRUCTURE can be a structure definition file.
	 * @param ConnectionFactoryInterface $factory
	 *        	Connection factory to use. If NULL, use DefaultConnectionFactory
	 * @throws \InvalidArgumentException
	 * @throws ConnectionException::
	 * @return \NoreSources\SQL\DBMS\ConnectionInterface|NULL
	 */
	public static function createConnection($settings = array(),
		ConnectionFactoryInterface $factory = null)
	{
		if (!Container::isArray($settings))
			$settings = [
				K::CONNECTION_TYPE => $settings
			];

		if (Container::keyExists($settings, K::CONNECTION_STRUCTURE))
		{
			$structure = $settings[K::CONNECTION_STRUCTURE];
			if (\is_string($structure))
			{
				if (!\file_exists($structure))
					throw new \InvalidArgumentException(
						K::CONNECTION_STRUCTURE . ' setting must be a ' .
						StructureElementInterface::class . ' or a valid file path');

				$factory = new StructureSerializerFactory();
				$structure = $factory->structureFromFile($structure);
			}

			$settings[K::CONNECTION_STRUCTURE] = $structure;
		}

		if (!($factory instanceof ConnectionFactoryInterface))
			$factory = new DefaultConnectionFactory();

		return $factory->createConnection($settings);
	}

	/**
	 * Create DBMS structure
	 *
	 * @param ConnectionInterface $connection
	 * @param StructureElementInterface $structure
	 *        	Structure lements to create
	 * @throws \Exception
	 * @throws \InvalidArgumentException
	 * @return unknown|\NoreSources\SQL\DBMS\Recordset|number|boolean|\NoreSources\SQL\DBMS\Recordset|number|boolean|unknown|\NoreSources\SQL\DBMS\Recordset|number|boolean
	 */
	public static function createStructure(ConnectionInterface $connection,
		StructureElementInterface $structure = null)
	{
		if (!($structure instanceof StructureElementInterface))
			$structure = $connection->getStructure();

		if (!($structure instanceof StructureElementInterface))
			throw new \Exception('No structure');

		if ($structure instanceof DatasourceStructure)
		{
			foreach ($structure as $s)
			{
				$result = self::createStructure($connection, $s);
				if (!$result)
					return $result;
			}
			return $result;
		}
		elseif ($structure instanceof NamespaceStructure)
		{
			/**
			 *
			 * @var \NoreSources\SQL\Statement\Structure\CreateNamespaceQuery $q
			 */
			$q = $connection->getStatementFactory()->newStatement(K::QUERY_CREATE_NAMESPACE,
				$structure);

			$q->identifier($structure);
			$statement = self::buildStatement($connection, $q, $structure);

			$result = $connection->executeStatement($statement);
			if (!$result)
				return $result;

			foreach ($structure as $e)
			{
				$result = self::createStructure($connection, $e);
				if (!$result)
					return $result;
			}

			return $result;
		}
		elseif ($structure instanceof TableStructure)
		{
			$q = $connection->getStatementFactory()->newStatement(K::QUERY_CREATE_TABLE, $structure);
			$statement = self::buildStatement($connection, $q, $structure);
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

		throw new \InvalidArgumentException(
			'Unsupported structure type ' . TypeDescription::getName($structure));
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
}

