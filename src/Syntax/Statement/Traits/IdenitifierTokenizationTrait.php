<?php

/**
 * Copyright © 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement\Traits;

use NoreSources\SQL\Structure\Identifier;
use NoreSources\SQL\Structure\NamespaceStructure;
use NoreSources\SQL\Syntax\TokenStream;
use NoreSources\SQL\Syntax\TokenStreamContextInterface;

trait IdenitifierTokenizationTrait
{

	protected function tokenizeIdentifier(TokenStream $stream,
		TokenStreamContextInterface $context, Identifier $identifier,
		$qualified = true)
	{
		$platform = $context->getPlatform();

		if (!$qualified)
			return $stream->identifier(
				$platform->quoteIdentifier($identifier->getLocalName()));

		if (\count($identifier->getPathParts()) > 1)
			return $stream->identifier(
				$platform->quoteIdentifierPath($identifier));

		$pivot = $context->getPivot();
		while ($pivot && !($pivot instanceof NamespaceStructure))
		{
			$pivot = $pivot->getParentElement();
		}

		if ($pivot)
		{
			$name = $identifier->getLocalName();
			$identifier = Identifier::make($pivot);
			$identifier->append($name);
		}

		return $stream->identifier(
			$platform->quoteIdentifierPath($identifier));
	}
}
