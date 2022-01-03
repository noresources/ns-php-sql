<?php
/**
 * Copyright © 2020 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement\Traits;

use NoreSources\Container\Container;
use NoreSources\SQL\Syntax\Statement\ParameterDataProviderInterface;
use NoreSources\SQL\Syntax\Statement\ResultColumnProviderInterface;
use NoreSources\SQL\Syntax\Statement\StatementDataInterface;
use NoreSources\SQL\Syntax\Statement\StatementTypeProviderInterface;
use NoreSources\Type\TypeConversion;
use NoreSources\Type\TypeDescription;

trait StatementSerializationTrait
{

	public function jsonSerialize()
	{
		$result = [];

		if ($this instanceof StatementTypeProviderInterface)
		{
			$result[StatementDataInterface::SERIALIZATION_TYPE] = $this->getStatementType();
		}

		if (TypeDescription::hasStringRepresentation($this))
		{
			$result[StatementDataInterface::SERIALIZATION_SQL] = TypeConversion::toString(
				$this);
		}

		if ($this instanceof ParameterDataProviderInterface)
		{
			$a = [];
			foreach ($this->getParameters() as $parameter)
			{
				$a[] = Container::createArray($parameter);
			}

			$result[StatementDataInterface::SERIALIZATION_PARAMETERS] = $a;
		}

		if ($this instanceof ResultColumnProviderInterface)
		{
			$a = [];
			foreach ($this->getResultColumns() as $column)
			{
				$a[] = Container::createArray($column);
			}

			$result[StatementDataInterface::SERIALIZATION_COLUMNS] = $a;
		}

		return $result;
	}

	public function serialize()
	{
		return \json_encode($this->jsonSerialize());
	}
}
