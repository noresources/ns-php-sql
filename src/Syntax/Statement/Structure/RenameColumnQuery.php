<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement\Structure;

use NoreSources\SQL\Structure\ColumnStructure;
use NoreSources\SQL\Structure\Identifier;
use NoreSources\SQL\Structure\StructureElementInterface;
use NoreSources\SQL\Structure\TableStructure;
use NoreSources\SQL\Syntax\TokenStream;
use NoreSources\SQL\Syntax\TokenStreamContextInterface;
use NoreSources\SQL\Syntax\Statement\TokenizableStatementInterface;
use NoreSources\SQL\Syntax\Statement\Traits\StatementTableTrait;

class RenameColumnQuery implements TokenizableStatementInterface,
	StructureOperationQueryInterface
{
	use StatementTableTrait;

	/**
	 *
	 * @param Identifier|string $from
	 * @param string $to
	 * @return $this
	 */
	public function rename($from, $to)
	{
		$this->from = Identifier::make($from);
		$p = $this->from->getParentIdentifier();
		if ($p && !$p->isEmpty())
			$this->table($p);

		$this->to = Identifier::make($to)->getLocalName();

		return $this;
	}

	/**
	 * * @return $this
	 */
	public function forStructure(StructureElementInterface $structure)
	{
		if ($structure instanceof ColumnStructure)
			return $this->rename($structure, $this->to);

		if ($structure instanceof TableStructure)
			return $this->table($structure);

		return $this;
	}

	public function getStatementType()
	{
		return 0;
	}

	public function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$platform = $context->getPlatform();
		return $stream->keyword('alter table')
			->space()
			->identifier($platform->quoteIdentifier($this->getTable()))
			->space()
			->keyword('rename column')
			->space()
			->identifier($platform->quoteIdentifier($this->from))
			->space()
			->keyword('to')
			->space()
			->identifier($platform->quoteIdentifier($this->to));
	}

	/**
	 *
	 * @var Identifier
	 */
	private $from;

	/**
	 *
	 * @var string
	 */
	private $to;
}
