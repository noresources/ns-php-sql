<?php
namespace NoreSources\SQL\Syntax;

interface TokenStreamExporterInterface
{

	/**
	 *
	 * @param TokenStream $stream
	 *        	Token stream
	 * @param TokenStreamContextInterface $context
	 *        	Context
	 * @return mixed TokenStream transformation
	 */
	function export(TokenStream $stream,
		TokenStreamContextInterface $context);
}
