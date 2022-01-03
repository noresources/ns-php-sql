<?php
/**
 * Copyright © 2020 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure\Traits;

trait ConstraintNameTrait
{

	/**
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->constraintName;
	}

	public function setName($name)
	{
		if (\is_string($name))
			$this->constraintName = $name;
		else
			$this->constraintName = null;
		return $this;
	}

	private $constraintName;
}
