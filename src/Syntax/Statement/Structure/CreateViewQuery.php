<?php
/**
 * Copyright © 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement\Structure;

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Structure\Identifier;
use NoreSources\SQL\Structure\NamespaceStructure;
use NoreSources\SQL\Structure\ViewStructure;
use NoreSources\SQL\Syntax\TokenStream;
use NoreSources\SQL\Syntax\TokenStreamContextInterface;
use NoreSources\SQL\Syntax\Statement\TokenizableStatementInterface;
use NoreSources\SQL\Syntax\Statement\Query\SelectQuery;
use NoreSources\SQL\Syntax\Statement\Structure\Traits\CreateFlagsTrait;

/**
 * CREATE VIEW statement
 *
 *
 * References
 * <dl>
 * <dt>SQLite</dt>
 * <dd>https://www.sqlite.org/lang_createview.html</dd>
 * <dt>MySQL</dt>
 * <dd>https://dev.mysql.com/doc/refman/8.0/en/create-view.html</dd>
 * <dt>PostgreSQL</dt>
 * <dd>https://www.postgresql.org/docs/9.2/sql-createview.html</dd>
 * </dl>
 */
class CreateViewQuery implements TokenizableStatementInterface
{

	use CreateFlagsTrait;

	/**
	 *
	 * @deprecated Use use K::CREATE_TEMPORARY
	 */
	const TEMPORARY = K::CREATE_TEMPORARY;

	/**
	 *
	 * @param string|Identifier $identifier
	 *        	View identifier
	 */
	public function __construct($identifier = null)
	{
		$this->viewIdentifier = null;
		$this->selectQuery = null;

		if ($identifier !== null)
			$this->identifier($identifier);
	}

	public function getStatementType()
	{
		return K::QUERY_CREATE_VIEW;
	}

	/**
	 *
	 * @param string|Identifier $identifier
	 *        	View identifier
	 * @return \NoreSources\SQL\Syntax\Statement\Structure\DropViewQuery
	 */
	public function identifier($identifier)
	{
		if ($identifier instanceof ViewStructure)
			$identifier = $identifier->getPath();

		$this->viewIdentifier = Identifier::make($identifier);

		return $this;
	}

	/**
	 *
	 * @deprecated Use createFlags()
	 * @param integer $flags
	 * @return \NoreSources\SQL\Syntax\Statement\Structure\CreateViewQuery
	 */
	public function flags($flags)
	{
		return $this->createFlags($flags);
	}

	/**
	 *
	 * @param SelectQuery $identifierAs
	 * @return \NoreSources\SQL\Syntax\Statement\Structure\CreateViewQuery
	 */
	public function select(SelectQuery $identifierAs)
	{
		$this->selectQuery = $identifierAs;
		return $this;
	}

	public function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$platform = $context->getPlatform();
		$platformCreateFlags = $platform->queryFeature(
			[
				K::FEATURE_CREATE,
				K::FEATURE_VIEW,
				K::FEATURE_CREATE_FLAGS
			], 0);

		$stream->keyword('create');

		$platformCreateFlags = $platform->queryFeature(
			[
				K::FEATURE_CREATE,
				K::FEATURE_VIEW,
				K::FEATURE_CREATE_FLAGS
			], 0);

		if ($this->getCreateFlags() & K::CREATE_TEMPORARY &&
			($platformCreateFlags & K::FEATURE_CREATE_TEMPORARY))
			$stream->space()->keyword('temporary');

		$stream->space()->keyword('view');
		if (($platformCreateFlags & K::FEATURE_CREATE_EXISTS_CONDITION))
		{
			$stream->space()
				->keyword('if')
				->space()
				->keyword('not')
				->space()
				->keyword('exists');
		}

		$stream->space();

		$parts = $this->viewIdentifier->getPathParts();
		if (\count($parts) > 1)
		{
			$stream->identifier(
				$context->getPlatform()
					->quoteIdentifierPath($parts));
		}
		else // Last chance to find the element namespace
		{
			$structure = $context->getPivot();
			if ($stream instanceof ViewStructure)
				$structure = $structure->getParentElement();

			if ($structure instanceof NamespaceStructure)
				$stream->identifier(
					$context->getPlatform()
						->quoteIdentifierPath($structure))
					->text('.');

			$stream->identifier(
				$context->getPlatform()
					->quoteIdentifier(
					$this->viewIdentifier->getLocalName()));
		}

		return $stream->space()
			->keyword('as')
			->space()
			->expression($this->selectQuery, $context);
	}

	/**
	 *
	 * @var integer
	 */
	private $identifierFlags;

	/**
	 * View name
	 *
	 * @var Identifier
	 */
	private $viewIdentifier;

	/**
	 *
	 * @var SelectQuery
	 */
	private $selectQuery;
}
