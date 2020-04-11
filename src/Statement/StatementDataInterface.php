<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\Statement;

use NoreSources\StringRepresentation;

/**
 * Aggregate interface
 */
interface StatementDataInterface extends InputData, OutputData, StringRepresentation
{
}