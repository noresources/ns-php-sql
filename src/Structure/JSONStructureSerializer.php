<?php
/**
 * Copyright © 2012-2018 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

use NoreSources\SQL\Constants as K;

class JSONStructureSerializer extends StructureSerializer implements \JsonSerializable
{

	/**
	 *
	 * @var integer
	 */
	public $jsonSerializeFlags;

	public function __construct(StructureElement $structure, $flags = 0)
	{
		parent::__construct($structure);
		$this->jsonSerializeFlags = $flags;
	}

	public function unserialize($serialized)
	{
		$json = json_decode($serialized);
		if (!is_object($json))
			throw new StructureException('Invalid JSON data');

		throw new \Exception('Not implemented');
	}

	public function serialize()
	{
		return json_encode($this->jsonSerialize(), $this->jsonSerializeFlags);
	}

	public function jsonSerialize()
	{
		if ($this->structureElement instanceof DatasourceStructure)
		{
			return $this->serializeDatasource($this->structureElement);
		}
		elseif ($this->structureElement instanceof TablesetStructure)
		{
			return $this->serializeTableset($this->structureElement);
		}
		elseif ($this->structureElement instanceof TableStructure)
		{
			return $this->serializeTable($this->structureElement);
		}
		elseif ($this->structureElement instanceof ColumnStructure)
		{
			return $this->serializeTableColumn($this->structureElement);
		}

		return array();
	}

	private function serializeDatasource(DatasourceStructure $structure)
	{
		$properties = [
			'name' => $structure->getName(),
			'kind' => 'datasource',
			'tablesets' => []
		];

		foreach ($structure as $tableName => $table)
		{
			$properties['tablesets'][$tableName] = $this->serializeTableset($table);
		}

		return $properties;
	}

	private function serializeTableset(TablesetStructure $structure)
	{
		$properties = [
			'tables' => []
		];

		foreach ($structure as $tableName => $table)
		{
			$properties['tables'][$tableName] = $this->serializeTable($table);
		}

		if (!($structure->parent() instanceof DatasourceStructure))
		{
			$properties = array_merge([
				'name' => $structure->getName(),
				'kind' => 'tableset'
			], $properties);
		}

		return $properties;
	}

	private function serializeTable(TableStructure $structure)
	{
		$properties = [
			'columns' => []
		];

		foreach ($structure as $columnName => $column)
		{
			$properties['columns'][$columnName] = $this->serializeTableColumn($column);
		}

		if (!($structure->parent() instanceof TablesetStructure))
		{
			$properties = array_merge([
				'name' => $structure->getName(),
				'kind' => 'table'
			], $properties);
		}

		return $properties;
	}

	private function serializeTableColumn(ColumnStructure $structure)
	{
		$properties = [];
		foreach ($structure->getColumnProperties() as $key => $value)
		{
			$properties[$key] = $value;
		}
		if (!($structure->parent() instanceof TableStructure))
		{
			$properties = array_merge([
				'name' => $structure->getName(),
				'kind' => 'column'
			], $properties);
		}

		return $properties;
	}
}

