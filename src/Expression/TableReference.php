<?php
/**
 * Copyright © 2012-2018 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */

// Namespace
namespace NoreSources\SQL\Expression;

// Aliases
use NoreSources\SQL\Statement\BuildContext;

/**
 * SQL Table reference in a SQL query
 */
class TableReference extends Table
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

	public function tokenize(TokenStream $stream, BuildContext $context)
	{
		parent::tokenize($stream, $context);
		if (strlen($this->alias))
			$stream->space()
				->keyword('as')
				->space()
				->identifier($context->escapeIdentifier($this->alias));

		return $stream;
	}
}
