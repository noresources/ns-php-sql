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
namespace NoreSources\SQL\Expression;

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

	public function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		parent::tokenize($stream, $context);
		if (strlen($this->alias))
			$stream->space()
				->keyword('as')
				->space()
				->identifier(
				$context->getStatementBuilder()
					->getPlatform()
					->quoteIdentifier($this->alias));

		return $stream;
	}
}
