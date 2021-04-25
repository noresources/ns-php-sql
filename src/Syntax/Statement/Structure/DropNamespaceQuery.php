<?php

/**
 * Copyright © 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement\Structure;

use NoreSources\Container;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Structure\DatasourceStructure;
use NoreSources\SQL\Structure\Identifier;
use NoreSources\SQL\Structure\NamespaceStructure;
use NoreSources\SQL\Syntax\TokenStream;
use NoreSources\SQL\Syntax\TokenStreamContextInterface;
use NoreSources\SQL\Syntax\Statement\StatementException;
use NoreSources\SQL\Syntax\Statement\TokenizableStatementInterface;
use NoreSources\SQL\Syntax\Statement\Structure\Traits\DropFlagsTrait;
use NoreSources\SQL\Syntax\Statement\Traits\IdenitifierTokenizationTrait;
use Psr\Log\LoggerInterface;

class DropNamespaceQuery implements TokenizableStatementInterface
{
	use DropFlagsTrait;
	use IdenitifierTokenizationTrait;

	public function __construct($identifier = null)
	{
		if ($identifier)
			$this->identifier($identifier);
	}

	public function getStatementType()
	{
		return K::QUERY_DROP_NAMESPACE;
	}

	public function identifier($identifier)
	{
		$this->namespaceIdentifier = Identifier::make($identifier);
		return $this;
	}

	public function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$platform = $context->getPlatform();
		$platformDropFlags = $platform->queryFeature(
			[
				K::FEATURE_DROP,
				K::FEATURE_NAMESPACE,
				K::FEATURE_DROP_FLAGS
			], 0);
		$cascade = ($this->getDropFlags() & K::DROP_CASCADE) &&
			($platformDropFlags & K::FEATURE_DROP_CASCADE);

		$stream->keyword('drop')
			->space()
			->keyword(K::KEYWORD_NAMESPACE)
			->space();

		if (($platformDropFlags & K::DROP_EXISTS_CONDITION))
		{
			$stream->keyword('if')
				->space()
				->keyword('exists')
				->space();
		}

		$this->tokenizeNamespaceIdentifier($stream, $context);

		if ($this->getDropFlags() & K::DROP_CASCADE)
		{
			if ($cascade)
				$stream->space()->keyword('cascade');
			elseif ($context instanceof LoggerInterface)
				$context->notice('CASCADE option is not supported');
		}

		return $stream;
	}

	protected function tokenizeNamespaceIdentifier(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$platform = $context->getPlatform();
		$identifier = $this->namespaceIdentifier;
		if (!isset($identifier))
		{
			$pivot = $context->getPivot();

			if ($pivot instanceof DatasourceStructure &&
				($namespaces = $pivot->getChildElements(
					NamespaceStructure::class)) &&
				\count($namespaces) == 1)
			{
				$pivot = Container::firstValue($namespaces);
			}
			else
				while ($pivot && !($pivot instanceof NamespaceStructure))
				{
					$pivot = $pivot->getParentElement();
				}

			if (!($pivot instanceof NamespaceStructure))
				throw new StatementException($this, 'Identifier not set');

			$identifier = Identifier::make($pivot);
		}

		return $stream->identifier(
			$platform->quoteIdentifier($identifier->getLocalName()));
	}

	/**
	 * Namespace name
	 *
	 * @var Identifier
	 */
	private $namespaceIdentifier;
}
