<?php
namespace NoreSources\SQL\DBMS\SQLite;

use NoreSources\SQL\Expression\ColumnDeclaration;
use NoreSources\SQL\Expression\TokenStream;
use NoreSources\SQL\Expression\TokenStreamContextInterface;

class SQLiteColumnDeclaration extends ColumnDeclaration
{

	/**
	 * SQLite doesn't have any kind of type constraints.
	 *
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\Expression\ColumnDeclaration::tokenizeTypeConstraints()
	 */
	public function tokenizeTypeConstraints(TokenStream $stream,
		TokenStreamContextInterface $context)
	{}
}
