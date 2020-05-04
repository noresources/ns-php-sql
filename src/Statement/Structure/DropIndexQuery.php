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
use NoreSources\SQL\Structure\IndexStructure;
use NoreSources\SQL\Structure\NamespaceStructure;

/**
 * DROP INDEX statement
 *
 * @see https://www.sqlite.org/lang_createindex.html
 */
class DropIndexQuery extends Statement
{

	public function __construct($name = null)
	{
		$this->name($name);
	}

	/**
	 *
	 * @param string $name
	 *        	Index name
	 *
	 * @return DropIndexQuery
	 */
	public function name($name)
	{
		$this->indexName = $name;
		return $this;
	}

	public function tokenize(TokenStream $stream, TokenStreamContextInterface $context)
	{
		$builderFlags = $context->getStatementBuilder()->getBuilderFlags(K::BUILDER_DOMAIN_GENERIC);
		$builderFlags |= $context->getStatementBuilder()->getBuilderFlags(
			K::BUILDER_DOMAIN_DROP_INDEX);

		$context->setStatementType(K::QUERY_DROP_INDEX);

		$structure = $context->getPivot();
		IF ($structure instanceof IndexStructure)
			$structure = $structure->getParentElement();

		/**
		 *
		 * @var TableStructure $tableStructure
		 */

		$stream->keyword('drop')
			->space()
			->keyword('index');

		if ($builderFlags & K::BUILDER_IF_EXISTS)
			$stream->keyword('if')
				->space()
				->keyword('exists');

		$stream->space();

		if (($builderFlags & K::BUILDER_SCOPED_STRUCTURE_DECLARATION) &&
			($structure instanceof NamespaceStructure))
			$stream->identifier($context->getStatementBuilder()
				->getCanonicalName($structure))
				->text('.');

		return $stream->identifier(
			$context->getStatementBuilder()
				->escapeIdentifier($this->indexName));
	}

	/**
	 *
	 * @var string
	 */
	private $indexName;
}