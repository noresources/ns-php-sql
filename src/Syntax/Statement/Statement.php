<?php
/**
 * Copyright © 2020 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement;

use NoreSources\Container\Container;
use NoreSources\SQL\Constants as K;
use NoreSources\Type\TypeConversion;
use NoreSources\Type\TypeDescription;

/**
 * SQL statement
 */
class Statement extends StatementData implements
	UnserializableStatementInterface
{

	/**
	 * Attempt to guess statement type from statement data
	 *
	 * @param mixed $data
	 * @return string|integer Statement type
	 */
	public static function statementTypeFromData($data)
	{
		if ($data instanceof StatementTypeProviderInterface)
			return $data->getStatementType();

		if (\is_integer($data))
			return $data;

		if (TypeDescription::hasStringRepresentation($data))
			$data = TypeConversion::toString($data);

		if (\is_string($data))
		{
			$regex = [
				'/^select\s+/i' => K::QUERY_SELECT,
				'/^insert\s+/i' => K::QUERY_INSERT,
				'/^update\s+/i' => K::QUERY_UPDATE,
				'/^delete\s+/i' => K::QUERY_DELETE,
				'/^create\s+/i' => K::QUERY_FAMILY_CREATE,
				'/^drop\s+/i' => K::QUERY_FAMILY_DROP
			];

			foreach ($regex as $r => $t)
			{
				if (preg_match($r, $data))
					return $t;
			}
		}

		return 0;
	}

	/**
	 *
	 * @param mixed ...$arguments
	 *        	Statement informatsions (ParameterData, ResultColumnMap, statement type, SQL
	 *        	string or any interface that provide one of this element)
	 */
	public function __construct(...$arguments)
	{
		foreach ($arguments as $argument)
		{
			if ($this->getStatementType() == 0)
				$this->initializeStatementType($argument);
			if ($this->getParameters() === null)
				$this->initializeParameterData($argument);
			if ($this->getResultColumns() === null)
				$this->initializeResultColumnData($argument);
			if ($this->getSQL() === null &&
				TypeDescription::hasStringRepresentation($argument))
				$this->setSQL(TypeConversion::toString($argument));
		}
	}

	public function unserialize($data)
	{
		$data = @\json_decode($data, true);
		if (\json_last_error() != JSON_ERROR_NONE)
			throw new StatementSerializationException(
				\json_last_error_msg(), \json_last_error());
		if (!\is_array($data))
			throw new StatementSerializationException(
				'Array expected. Got ' . TypeDescription::getName($data),
				StatementSerializationException::CONTENT);

		if (($type = Container::keyValue($data, self::SERIALIZATION_TYPE)))
			$this->initializeStatementType($type);

		if (($sql = Container::keyValue($data, self::SERIALIZATION_SQL)))
			$this->setSQL($sql);

		if (($parameters = Container::keyValue($data,
			self::SERIALIZATION_PARAMETERS)))
		{
			$this->initializeParameterData(null);
			$map = $this->getParameters();

			foreach ($parameters as $index => $p)
			{
				$map->setParameter($index, $p[ParameterData::KEY],
					$p[ParameterData::DBMSNAME]);
			}
		}

		if (($columns = Container::keyValue($data,
			self::SERIALIZATION_COLUMNS)))
		{
			$this->initializeResultColumnData();
			$list = $this->getResultColumns();
			foreach ($columns as $index => $column)
				$list->setColumn($index, $column);
		}
	}
}

