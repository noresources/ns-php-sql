<?php

/**
 * Copyright © 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure\Traits;

use NoreSources\TypeConversion;
use NoreSources\TypeDescription;
use NoreSources\Expression\ExpressionInterface;
use NoreSources\SQL\Syntax\Statement\Traits\WhereConstraintTrait;

/**
 * Implementation of interface IndexDescriptionInterface
 */
trait IndexDescriptionTrait
{
	use WhereConstraintTrait;

	public function getConstraintExpression()
	{
		return $this->whereConstraints;
	}

	/**
	 *
	 * @param integer $flags
	 * @return$this
	 */
	public function flags($flags)
	{
		$this->indexFlags = $flags;
		return $this;
	}

	/**
	 *
	 * @return integer
	 */
	public function getIndexFlags()
	{
		if (isset($this->indexFlags))
			return $this->indexFlags;
		return 0;
	}

	/**
	 *
	 * @param array $args...
	 *        	Column names
	 * @return $this
	 */
	public function columns()
	{
		$c = func_num_args();
		if ($c && !isset($this->indexColumns))
			$this->indexColumns = [];
		for ($i = 0; $i < $c; $i++)
		{
			$column = func_get_arg($i);
			if ($column instanceof ExpressionInterface)
				$this->indexColumns[] = $column;
			elseif (TypeDescription::hasStringRepresentation($column))
				$this->indexColumns[] = TypeConversion::toString(
					$column);
		}

		return $this;
	}

	/**
	 *
	 * @return Evaluable[]
	 */
	public function getColumns()
	{
		if (!isset($this->indexColumns))
			return [];
		return $this->indexColumns;
	}

	/**
	 *
	 * @var integer
	 */
	private $indexFlags;

	/**
	 *
	 * @var Evaluable[]
	 */
	private $indexColumns;
}
