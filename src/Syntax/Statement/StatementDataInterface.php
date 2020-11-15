<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement;

use NoreSources\StringRepresentation;

/**
 * Aggregate interface
 * - Parameter informations
 * - Statement type
 * - Result column informations
 * - String representation must output the SQL statement string.
 */
interface StatementDataInterface extends StatementInputDataInterface,
	StatementOutputDataInterface, StringRepresentation
{
}