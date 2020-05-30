<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */

//
namespace NoreSources\SQL\Statement\Query;

class UnionClause
{

	/**
	 *
	 * @var SelectQuery
	 */
	public $query;

	/**
	 *
	 * @var boolean
	 */
	public $all;

	public function __construct(SelectQuery $q, $all = false)
	{
		$this->query = $q;
		$this->all = $all;
	}
}
