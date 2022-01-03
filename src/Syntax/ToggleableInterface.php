<?php
/**
 * Copyright © 2020 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax;

/**
 * An expression which have a negated counterparts.
 */
interface ToggleableInterface
{

	/**
	 *
	 * @param boolean|null $value
	 *        	If NULL, invert the current state, otherwise the the state ttrue or false.
	 * @return $this
	 */
	function toggle($value = null);

	/**
	 *
	 * @return boolean
	 */
	function getToggleState();
}
