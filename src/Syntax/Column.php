<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax;

use NoreSources\Container\Container;
use NoreSources\SQL\Structure\ColumnStructure;
use NoreSources\SQL\Structure\Identifier;

/**
 * Table column reference
 */
class Column extends Identifier implements
	TokenizableExpressionInterface
{

	public function __construct($path)
	{
		parent::__construct($path);
	}

	public function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$target = $context->findColumn($this->path);
		if ($target instanceof ColumnStructure)
		{
			$parts = explode('.', $this->path);
			foreach ($parts as $part)
			{
				if ($context->isAlias($part))
					return $stream->identifier(
						Container::implodeValues($parts, '.',
							[
								$context->getPlatform(),
								'quoteIdentifier'
							]));
			}

			return $stream->identifier(
				$context->getPlatform()
					->quoteIdentifierPath($target));
		}
		else
			return $stream->identifier(
				$context->getPlatform()
					->quoteIdentifier($this->path));
	}
}