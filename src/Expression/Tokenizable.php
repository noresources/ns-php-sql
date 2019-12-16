<?php
namespace NoreSources\SQL\Expression;

use NoreSources\SQL\Statement\BuildContext;

interface Tokenizable
{

	/**
	 *
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\Tokenizable::tokenize()
	 * @return TokenStream
	 */
	function tokenize(TokenStream &$stream, BuildContext $context);
}
