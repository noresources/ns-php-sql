<?php

/**
 * Copyright © 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement\Structure\Traits;

trait CreateFlagsTrait
{

	/**
	 *
	 * @param integer $flags
	 * @return $this
	 */
	public function createFlags($flags)
	{
		$this->standardCreateFlags = $flags;
		return $this;
	}

	/**
	 *
	 * @return number
	 */
	public function getCreateFlags()
	{
		if (!isset($this->standardCreateFlags))
			return 0;
		return $this->standardCreateFlags;
	}

	/**
	 *
	 * @var integer
	 */
	private $standardCreateFlags;
}
