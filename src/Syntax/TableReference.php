<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax;

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
				$context->getPlatform()
					->quoteIdentifier($this->alias));

		return $stream;
	}
}
