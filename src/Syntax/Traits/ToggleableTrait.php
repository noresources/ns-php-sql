<?php
/**
 * Copyright © 2020 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Traits;

trait ToggleableTrait
{

	public function toggle($value = null)
	{
		if (\is_null($value))
			$this->toggleState = !$this->getToggleState();
		else
			$this->toggleState = ($value ? true : false);
	}

	public function getToggleState()
	{
		return ($this->toggleState === false) ? false : true;
	}

	private $toggleState;
}
