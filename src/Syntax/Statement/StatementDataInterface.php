<?php
/**
 * Copyright © 2020 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement;

use NoreSources\Type\StringRepresentation;

/**
 * Aggregate interface
 * - Parameter informations
 * - Statement type
 * - Result column informations
 * - String representation must output the SQL statement string.
 */
interface StatementDataInterface extends ParameterDataProviderInterface,
	StatementTypeProviderInterface, ResultColumnProviderInterface,
	StringRepresentation
{

	/**
	 *
	 * @return string Serialized statement data
	 */
	function serialize();

	const SERIALIZATION_SQL = 'sql';

	const SERIALIZATION_TYPE = 'type';

	const SERIALIZATION_COLUMNS = 'columns';

	const SERIALIZATION_PARAMETERS = 'parameters';
}