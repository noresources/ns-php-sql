<?php
/**
 * Copyright © 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement\Structure;

use NoreSources\Container;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Structure\NamespaceStructure;
use NoreSources\SQL\Structure\Identifier;
use NoreSources\SQL\Structure\TableStructure;
use NoreSources\SQL\Syntax\TokenStream;
use NoreSources\SQL\Syntax\TokenStreamContextInterface;
use NoreSources\SQL\Syntax\Statement\TokenizableStatementInterface;

/**
 * DROP INDEX statement
 *
 * References
 * <dl>
 * <dt>SQLite</dt>
 * <dd>https://www.sqlite.org/lang_createindex.html</dd>
 * <dt>MySQL</dt>
 * <dd></dd>
 * <dt>PostgreSQL</dt>
 * <dd></dd>
 * </dl>
 */
class DropIndexQuery implements TokenizableStatementInterface
{

	public function __construct($identifier = null)
	{
		$this->indexIdentifier = null;
		if ($identifier != null)
			$this->identifier($identifier);
	}

	public function getStatementType()
	{
		return K::QUERY_DROP_INDEX;
	}

	/**
	 *
	 * @param string|Identifier $identifier
	 *        	Index identifier
	 * @return \NoreSources\SQL\Syntax\Statement\Structure\DropIndexQuery
	 */
	public function identifier($identifier)
	{
		$this->indexIdentifier = Identifier::make(
			$identifier);

		return $this;
	}

	public function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$platform = $context->getPlatform();

		$existsCondition = $platform->queryFeature(
			[
				K::FEATURE_DROP,
				K::FEATURE_INDEX,
				K::FEATURE_EXISTS_CONDITION
			], false);

		$stream->keyword('drop')
			->space()
			->keyword('index');
		if ($existsCondition)
		{
			$stream->space()
				->keyword('if')
				->space()
				->keyword('exists');
		}

		$stream->space();
		return $this->tokenizeIdentifier($stream, $context,
			$this->indexIdentifier);
	}

	protected function tokenizeIdentifier(TokenStream $stream,
		TokenStreamContextInterface $context,
		Identifier $identifier)
	{
		$platform = $context->getPlatform();

		$scoped = $platform->queryFeature(
			[
				K::FEATURE_INDEX,
				K::FEATURE_SCOPED
			], false);
		if ($scoped)
		{
			$namespace = $this->getNamespaceName($context);
			if ($namespace)
				$stream->identifier(
					$platform->quoteIdentifier($namespace))
					->text('.');
		}

		return $stream->identifier(
			$platform->quoteIdentifier($identifier->getLocalName()));
	}

	public function getNamespaceName(
		TokenStreamContextInterface $context = null)
	{
		if (isset($this->indexIdentifier))
		{
			$parts = $this->indexIdentifier->getPathParts();
			if (\count($parts) > 1)
				return Container::firstValue($parts);
		}

		if (!$context)
			return null;

		$structure = $context->getPivot();
		if (!$structure)
			return null;

		if ($structure instanceof TableStructure)
			$structure = $structure->getParentElement();

		if ($structure instanceof NamespaceStructure)
			return $structure->getName();

		return null;
	}

	/**
	 * Index name
	 *
	 * @var Identifier
	 */
	private $indexIdentifier;
}
