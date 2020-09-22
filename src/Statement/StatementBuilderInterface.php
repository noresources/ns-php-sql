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

use NoreSources\SQL\DataSerializerInterface;
use NoreSources\SQL\DBMS\PlatformProviderInterface;
use NoreSources\SQL\Expression\TokenStream;
use NoreSources\SQL\Expression\TokenStreamContextInterface;
use NoreSources\SQL\Structure\StructureElementInterface;

/**
 * Build a SQL statement string to be used in a SQL engine
 */
interface StatementBuilderInterface extends DataSerializerInterface,
	StatementFactoryInterface, PlatformProviderInterface
{

	/**
	 * Escape SQL identifier to be inserted in a SQL statement.
	 *
	 * @param string $identifier
	 */
	function escapeIdentifier($identifier);

	/**
	 *
	 * Get a DBMS-compliant parameter name
	 *
	 * @param string $name
	 *        	Parameter name
	 * @param ParameterData $parameters
	 *        	The already assigned parameters
	 *
	 *        	NULL may be passed when the builder does not require the
	 *        	previou
	 */
	function getParameter($name, ParameterData $parameters = null);

	/**
	 *
	 * @param StructureElementInterface|\Traversable|string $structure
	 * @return string
	 */
	function getCanonicalName($structure);

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