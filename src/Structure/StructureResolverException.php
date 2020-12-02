<?php
/**
 * Copyright © 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

/**
 * Exception raised while attempting to resolve structure element path or alias
 */
class StructureResolverException extends \Exception
{

	public function __construct($path, $elementType = '')
	{
		parent::__construct(
			'"' . $path . '"' .
			(\strlen($elementType) ? ' ' . $elementType : '') .
			' not found');
	}
}