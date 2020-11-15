<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

use NoreSources\SQL\Structure\Traits\StructureElementTrait;
use NoreSources\SQL\Syntax\TableReference;
use NoreSources\SQL\Syntax\Statement\Traits\WhereConstraintTrait;
use ArrayObject;

class IndexStructure implements StructureElementInterface
{
	use WhereConstraintTrait;
	use StructureElementTrait;

	const UNIQUE = 0x01;

	/**
	 *
	 * @param stringn $name
	 *        	Index name
	 *        	Index name
	 * @param StructureElementInterface $parent
	 *        	Parent structure
	 */
	public function __construct($name, StructureElementInterface $parent)
	{
		$this->initializeStructureElement($name, $parent);
		$this->initializeWhereConstraints();
		$this->indexColumns = new \ArrayObject();
		$this->indexTable = null;
		$this->indexFlags = 0;
	}

	public function __clone()
	{
		$this->cloneStructureElement();
	}

	/**
	 *
	 * @return \NoreSources\SQL\Structure\TableReference
	 */
	public function getIndexTable()
	{
		return $this->indexTable;
	}

	public function getIndexFlags()
	{
		return $this->indexFlags;
	}

	/**
	 *
	 * @return ArrayObject
	 */
	public function getIndexColumns()
	{
		return $this->indexColumns;
	}

	public function getIndexConstraints()
	{
		return $this->whereConstraints;
	}

	public function setIndexFlags($flags)
	{
		$this->indexFlags = $flags;
	}

	/**
	 *
	 * @param TableStructure $table
	 */
	public function setIndexTable(TableStructure $table)
	{
		$this->indexTable = $table;
	}

	/**
	 *
	 * @param ColumnStructure $column
	 *
	 * @todo Accept any expression as a column
	 */
	public function addIndexColumn(ColumnStructure $column)
	{
		$this->indexColumns->append($column);
	}

	/**
	 *
	 * @var TableReference
	 */
	private $indexTable;

	/**
	 *
	 * @var \ArrayObject
	 */
	private $indexColumns;

	/**
	 *
	 * @var integer
	 */
	private $indexFlags;
}