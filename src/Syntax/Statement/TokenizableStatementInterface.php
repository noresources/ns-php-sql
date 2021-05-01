<?php

/**
 * Copyright © 2020 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement;

use NoreSources\SQL\Syntax\TokenizableExpressionInterface;

interface TokenizableStatementInterface extends
	StatementTypeProviderInterface, TokenizableExpressionInterface
{
}
