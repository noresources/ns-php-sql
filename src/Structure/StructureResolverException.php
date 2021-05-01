<?php
/**
 * Copyright © 2020 - 2021 by Renaud Guillard (dev@nore.fr)
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

	public function __construct($path, $elementType = '', $pivot = null)
	{
		$message = '"' . $path . '"' .
			(\strlen($elementType) ? ' ' . $elementType : '') .
			' not found';
		if ($pivot instanceof Identifier)
			$message .= ' in ' . $pivot->getPath() . ' context';
		parent::__construct($message);
	}
}