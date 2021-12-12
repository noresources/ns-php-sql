<?php

/**
 * Copyright © 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\Explorer;

use NoreSources\SQL\DBMS\ConnectionProviderInterface;
use NoreSources\Type\TypeDescription;

class StructureExplorerMethodNotImplementedException extends StructureExplorerException
{

	public function __construct(StructureExplorerInterface $explorer,
		$methodName)
	{
		$methodName = \preg_replace('/.*::(.*)/', '$1', $methodName);
		$cls = $explorer;
		if ($cls instanceof ConnectionProviderInterface)
			$csl = $cls->getConnection();

		$message = TypeDescription::getName($cls) . '::' .
			TypeDescription::getLocalName($methodName, true) .
			'() not implemented';
		parent::__construct($message);
	}
}
