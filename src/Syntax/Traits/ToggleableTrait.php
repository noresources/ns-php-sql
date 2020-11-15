<?php
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
