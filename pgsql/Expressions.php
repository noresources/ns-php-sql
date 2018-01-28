<?php

/**
 * Copyright © 2012-2017 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */

/**
 *
 * @package SQL
 */
namespace NoreSources\SQL;

class PostgreSQLBinaryData extends BinaryData
{

	public function __construct(Datasource $datasource)
	{
		parent::__construct($datasource);
	}

	public function expressionString($options = null)
	{
		return protectString(parent::expressionString($options));
	}
}
