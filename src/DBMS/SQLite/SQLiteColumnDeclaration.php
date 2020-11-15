<?php
namespace NoreSources\SQL\DBMS\SQLite;

use NoreSources\SQL\Syntax\ColumnDeclaration;
use NoreSources\SQL\Syntax\TokenStream;
use NoreSources\SQL\Syntax\TokenStreamContextInterface;

class SQLiteColumnDeclaration extends ColumnDeclaration
{

	/**
	 * SQLite doesn't have any kind of type constraints.
	 *
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\Syntax\ColumnDeclaration::tokenizeTypeConstraints()
	 */
	public function tokenizeTypeConstraints(TokenStream $stream,
		TokenStreamContextInterface $context)
	{}
}
