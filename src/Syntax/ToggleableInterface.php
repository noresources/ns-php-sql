<?php
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
