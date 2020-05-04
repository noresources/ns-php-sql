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
use NoreSources\SQL\Structure\NamespaceStructure;
use NoreSources\SQL\Structure\ViewStructure;

/**
 * DROP VIEW statement
 *
 * References
 * <dl>
 * <dt>SQLite</dt>
 * <dd></dd>
 * <dt>MySQL</dt>
 * <dd></dd>
 * <dt>PostgreSQL</dt>
 * <dd></dd>
 * </dl>
 */
class DropViewQuery extends Statement
{

	public function __construct($name = null)
	{
		$this->viewName = '';
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

	public function tokenize(TokenStream $stream, TokenStreamContextInterface $context)
	{
		$builderFlags = $context->getStatementBuilder()->getBuilderFlags(K::BUILDER_DOMAIN_GENERIC);
		$builderFlags |= $context->getStatementBuilder()->getBuilderFlags(
			K::BUILDER_DOMAIN_DROP_VIEW);

		$context->setStatementType(K::QUERY_DROP_VIEW);

		$stream->keyword('drop')
			->space()
			->keyword('view');
		if ($builderFlags & K::BUILDER_IF_EXISTS)
		{
			$stream->space()
				->keyword('if')
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
		{
			$stream->identifier($context->getStatementBuilder()
				->escapeIdentifier($this->viewName));
		}

		return $stream;
	}

	/**
	 * View name
	 *
	 * @var string
	 */
	private $viewName;
}
