<?php

/**
 * Copyright © 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement\Structure\Traits;

use NoreSources\SQL\Structure\Identifier;

trait IdentifierPropertyTrait
{

	/**
	 *
	 * @param Identifier $identifier
	 * @return $this
	 */
	public function identifier($identifier)
	{
		$this->structureIdentifier = Identifier::make($identifier);
		return $this;
	}

	/**
	 *
	 * @return \NoreSources\SQL\Structure\Identifier
	 */
	public function getIdentifier()
	{
		return $this->structureIdentifier;
	}

	/**
	 *
	 * @var Identifier
	 */
	protected $structureIdentifier;
}
