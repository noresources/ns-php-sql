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

	public function __construct($name = null)
	{
		$this->viewFlags = 0;
		$this->viewName = '';
		$this->selectQuery = null;

		if ($name)
			$this->name($name);
	}

	/**
	 *
	 * @param string $name
	 *        	View name
	 * @return \NoreSources\SQL\Statement\Structure\DropViewQuery
	 */
	public function name($name)
	{
		if ($name instanceof ViewStructure)
			$name = $name->getName();
		$this->viewName = $name;
		return $this;
	}

	public function flags($flags)
	{
		$this->viewFlags = $flags;
		return $this;
	}

	/**
	 *
	 * @param SelectQuery $viewAs
	 * @return \NoreSources\SQL\Statement\Structure\CreateViewQuery
	 */
	public function select(SelectQuery $viewAs)
	{
		$this->selectQuery = $viewAs;
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
		{
			$stream->space()->keyword('temporary');
		}

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
			$structure = $context->getPivot();
			if ($structure instanceof ViewStructure && $structure->getName == $this->viewName)
			{
				$stream->identifier($context->getStatementBuilder()
					->getCanonicalName($structure));
			}
			if ($structure instanceof NamespaceStructure)
			{
				$stream->identifier($context->getStatementBuilder()
					->getCanonicalName($structure))
					->text('.')
					->identifier(
					$context->getStatementBuilder()
						->escapeIdentifier($this->viewName));
			}
			else
				$stream->identifier(
					$context->getStatementBuilder()
						->escapeIdentifier($this->viewName));
		}
		else
			$stream->identifier($context->getStatementBuilder()
				->escapeIdentifier($this->viewName));

		return $stream->space()
			->keyword('as')
			->space()
			->expression($this->selectQuery, $context);
	}

	/**
	 *
	 * @var integer
	 */
	private $viewFlags;

	/**
	 * View name
	 *
	 * @var string
	 */
	private $viewName;

	private $selectQuery;
}
