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

use NoreSources\SQL\Structure\TableStructure;

class Table extends StructureElementIdentifier
{

	public function __construct($path)
	{
		parent::__construct($path);
	}

	public function tokenize(TokenStream $stream, TokenStreamContext $context)
	{
		$target = $context->findTable($this->path);

		if ($target instanceof TableStructure)
		{
			$parts = explode('.', $this->path);
			foreach ($parts as $part)
			{
				if ($context->isAlias($part))
					return $stream->identifier($context->escapeIdentifierPath($parts));
			}

			return $stream->identifier($context->getCanonicalName($target));
		}
		else
			return $stream->identifier($context->escapeIdentifier($this->path));
	}
}