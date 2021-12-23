<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement\Structure;

use NoreSources\Expression\Identifier;
use NoreSources\SQL\Structure\StructureElementInterface;
use NoreSources\SQL\Structure\TableStructure;
use NoreSources\SQL\Syntax\TokenStream;
use NoreSources\SQL\Syntax\TokenStreamContextInterface;
use NoreSources\SQL\Syntax\Statement\TokenizableStatementInterface;
use NoreSources\SQL\Syntax\Statement\Traits\StatementTableTrait;

abstract class RenameTableQuery implements
	TokenizableStatementInterface
{
	use StatementTableTrait;

	/**
	 *
	 * @param Identifier|TableStructure|string $from
	 *        	Table to rename
	 * @param Identifier|TableStructure|string $to
	 *        	New table name
	 * @return $this
	 */
	public function rename($from, $to)
	{
		$this->table($from);
		$this->to = Identifier::make($to)->getLocalName();
		return $this;
	}

	public function forStructure(StructureElementInterface $structure)
	{
		return $this->table($structure);
	}

	public function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$platform = $context->getPlatform();
		return $stream->keyword('alter table')
			->space()
			->identifier(
			$platform->quoteIdentifierPath($this->getTable()))
			->space()
			->keyword('to')
			->space()
			->identifier($platform->quoteIdentifier($this->to));
	}

	public function getStatementType()
	{
		return 0;
	}

	/**
	 *
	 * @var string
	 */
	private $to;
}
