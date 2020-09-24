<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\Statement;

use NoreSources\SQL\DBMS\PlatformProviderInterface;
use NoreSources\SQL\Expression\TokenStream;
use NoreSources\SQL\Expression\TokenStreamContextInterface;

/**
 * Build a SQL statement string to be used in a SQL engine
 */
interface StatementBuilderInterface extends StatementFactoryInterface,
	PlatformProviderInterface
{

	/**
	 *
	 * @param TokenStream $stream
	 *        	A token stream containing statement tokens
	 * @param TokenStreamContextInterface $context
	 *        	The stream context used to fill the token stream
	 * @return StatementData
	 */
	function finalizeStatement(TokenStream $stream,
		TokenStreamContextInterface $context);
}