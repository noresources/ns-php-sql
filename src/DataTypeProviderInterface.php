<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL;

interface DataTypeProviderInterface
{

	/**
	 *
	 * @return integer Data type
	 */
	function getDataType();
}
