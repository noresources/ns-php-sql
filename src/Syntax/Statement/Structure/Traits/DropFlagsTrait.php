<?php

/**
 * Copyright © 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement\Structure\Traits;

trait DropFlagsTrait
{

	/**
	 *
	 * @param integer $flags
	 * @return $this
	 */
	public function dropFlags($flags)
	{
		$this->standardDropFlags = $flags;
		return $this;
	}

	/**
	 *
	 * @return number
	 */
	public function getDropFlags()
	{
		if (!isset($this->standardDropFlags))
			return 0;
		return $this->standardDropFlags;
	}

	/**
	 *
	 * @var integer
	 */
	private $standardDropFlags;
}
