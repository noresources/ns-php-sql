<?php

// Namespace
namespace NoreSources\SQL;

// Aliases
use NoreSources\SQL\Expression as X;

/**
 * SQL Table reference in a SQL query
 */
class TableReference extends X\Table
{

	/**
	 * Table alias
	 * 
	 * @var string|null 
	 */
	public $alias;

	public function __construct($path, $alias = null)
	{
		parent::__construct($path);
		$this->alias = $alias;
	}
}
