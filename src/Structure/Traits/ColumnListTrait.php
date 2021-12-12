<?php

/**
 * Copyright © 2020 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure\Traits;

use NoreSources\Container\Container;
use NoreSources\SQL\Structure\Identifier;

trait ColumnListTrait
{

	/**
	 *
	 * @return array
	 */
	public function getColumns()
	{
		return $this->columnNameList;
	}

	public function append($name)
	{
		$name = Identifier::make($name);
		$name = $name->getLocalName();
		if (Container::valueExists($this->columnNameList, $name))
			return $this;

		$this->columnNameList[] = $name;
		return $this;
	}

	/**
	 *
	 * @var array
	 */
	private $columnNameList;
}
