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
namespace NoreSources\SQL\Statement\Structure;

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Expression\StructureElementIdentifier;
use NoreSources\SQL\Expression\TokenStream;
use NoreSources\SQL\Expression\TokenStreamContextInterface;
use NoreSources\SQL\Statement\Statement;
use NoreSources\SQL\Statement\Query\SelectQuery;
use NoreSources\SQL\Structure\NamespaceStructure;
use NoreSources\SQL\Structure\ViewStructure;

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
class CreateViewQuery extends Statement
{

	const TEMPORARY = 0x01;

	/**
	 *
	 * @param string|StructureElementIdentifier|IndexStructure $identifier
	 *        	View identifier
	 */
	public function __construct($identifier = null)
	{
		$this->viewFlags = 0;
		$this->viewIdentifier = null;
		$this->selectQuery = null;

		if ($identifier !== null)
			$this->identifier($identifier);
	}

	/**
	 *
	 * @param string|StructureElementIdentifier|IndexStructure $identifier
	 *        	View identifier
	 * @return \NoreSources\SQL\Statement\Structure\DropViewQuery
	 */
	public function identifier($identifier)
	{
		if ($identifier instanceof ViewStructure)
			$identifier = $identifier->getPath();

		if ($identifier instanceof StructureElementIdentifier)
			$this->viewIdentifier = $identifier;
		else
			$this->viewIdentifier = new StructureElementIdentifier(\strval($identifier));

		return $this;
	}

	/**
	 *
	 * @param integer $flags
	 * @return \NoreSources\SQL\Statement\Structure\CreateViewQuery
	 */
	public function flags($flags)
	{
		$this->viewFlags = $flags;
		return $this;
	}

	/**
	 *
	 * @param SelectQuery $identifierAs
	 * @return \NoreSources\SQL\Statement\Structure\CreateViewQuery
	 */
	public function select(SelectQuery $identifierAs)
	{
		$this->selectQuery = $identifierAs;
		return $this;
	}

	public function tokenize(TokenStream $stream, TokenStreamContextInterface $context)
	{
		$builderFlags = $context->getStatementBuilder()->getBuilderFlags(K::BUILDER_DOMAIN_GENERIC);
		$builderFlags |= $context->getStatementBuilder()->getBuilderFlags(
			K::BUILDER_DOMAIN_CREATE_VIEW);

		$context->setStatementType(K::QUERY_CREATE_VIEW);

		$stream->keyword('create');

		if ($this->viewFlags & self::TEMPORARY)
			$stream->space()->keyword('temporary');

		$stream->space()->keyword('view');
		if ($builderFlags & K::BUILDER_IF_NOT_EXISTS)
		{
			$stream->space()
				->keyword('if')
				->space()
				->keyword('not')
				->space()
				->keyword('exists');
		}

		$stream->space();

		if ($builderFlags & K::BUILDER_SCOPED_STRUCTURE_DECLARATION)
		{
			$parts = $this->viewIdentifier->getPathParts();
			if (\count($parts) > 1)
			{
				$stream->identifier($builder->getCanonicalName($parts));
			}
			else // Last chance to find the element namespace
			{
				$structure = $context->getPivot();
				if ($stream instanceof ViewStructure)
					$structure = $structure->getParentElement();

				if ($structure instanceof NamespaceStructure)
					$stream->identifier($builder->getCanonicalName($structure))
						->text('.');

				$stream->identifier($builder->escapeIdentifier($this->viewIdentifier->path));
			}
		}
		else
			$stream->identifier(
				$context->getStatementBuilder()
					->escapeIdentifier($this->viewIdentifier));

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
	 * @var StructureElementIdentifier
	 */
	private $viewIdentifier;

	/**
	 *
	 * @var SelectQuery
	 */
	private $selectQuery;
}
