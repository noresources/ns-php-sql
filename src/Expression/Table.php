<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\Expression;

use NoreSources\Container;
use NoreSources\SQL\Structure\TableStructure;

/**
 * Table name reference
 */
class Table extends StructureElementIdentifier implements TokenizableExpressionInterface
{

	/**
	 *
	 * @param string $path
	 *        	Dot-separated structure path
	 */
	public function __construct($path)
	{
		parent::__construct($path);
	}

	public function tokenize(TokenStream $stream, TokenStreamContextInterface $context)
	{
		$target = $context->findTable($this->path);

		if ($target instanceof TableStructure)
		{
			$parts = explode('.', $this->path);
			foreach ($parts as $part)
			{
				if ($context->isAlias($part))
					return $stream->identifier(
						Container::implodeValues($parts, '.',
							[
								$context->getStatementBuilder(),
								'escapeIdentifier'
							]));
			}

			return $stream->identifier($context->getStatementBuilder()
				->getCanonicalName($target));
		}
		else
			return $stream->identifier(
				$context->getStatementBuilder()
					->escapeIdentifier($this->path));
	}
}