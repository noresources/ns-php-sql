<?php

/**
 * Copyright © 2020 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS;

use NoreSources\Container;
use NoreSources\TypeDescription;
use NoreSources\SQL\Structure\ColumnStructure;
use NoreSources\SQL\Structure\DatasourceStructure;
use NoreSources\SQL\Structure\NamespaceStructure;
use NoreSources\SQL\Structure\Identifier;
use NoreSources\SQL\Structure\StructureResolver;
use NoreSources\SQL\Structure\TableStructure;

abstract class AbstractStructureExplorer implements
	StructureExplorerInterface
{

	public function getStructure()
	{
		$datasource = new DatasourceStructure();
		$namespaces = $this->tryMethod('getNamespaceNames', [], []);

		$namespaces[] = null;

		foreach ($namespaces as $namespaceName)
		{
			$namespace = null;
			if ($namespaceName)
				$namespace = new NamespaceStructure($namespaceName,
					$datasource);
			else
				$namespace = $datasource;

			$tables = $this->tryMethod('getTableNames', [
				$namespace
			], []);

			foreach ($tables as $tableName)
			{
				$table = new TableStructure($tableName, $namespace);
				$namespace->appendElement($table);

				$tableIdentifier = Identifier::make(
					$table);

				$columns = $this->tryMethod('getTableColumnNames',
					[
						$tableIdentifier
					], []);
				foreach ($columns as $columnName)
				{
					$columnDescription = $this->tryMethod(
						'getTableColumn', [
							$table,
							$columnName
						], []);

					$column = new ColumnStructure($columnName, $table);

					foreach ($columnDescription as $key => $value)
						$column->setColumnProperty($key, $value);
					$table->appendElement($column);
				}

				$primaryKey = $this->tryMethod(
					'getTablePrimaryKeyConstraint', [
						$tableIdentifier
					], null);

				if ($primaryKey)
					$table->addConstraint($primaryKey);

				$foreignKeys = $this->tryMethod(
					'getTableForeignKeyConstraints',
					[
						$tableIdentifier
					], []);

				foreach ($foreignKeys as $constraint)
					$table->addConstraint($constraint);

				$indexes = $this->tryMethod('getTableIndexes',
					[
						$tableIdentifier
					], []);
				foreach ($indexes as $index)
					$table->addConstraint($index);
			}

			if ($namespace instanceof NamespaceStructure)
				$datasource->appendElement($namespace);
		}

		// PAss 2 - indexes, views and constraints

		$resolver = new StructureResolver($datasource);

		foreach ($namespaces as $namespaceName)
		{
			$namespace = ($namespaceName ? $datasource[$namespaceName] : $datasource);

			$views = $this->tryMethod('getViewNames', [
				$namespace
			], []);
		}

		return $datasource;
	}

	public function getNamespaceNames()
	{
		$this->notImplemented(__METHOD__);
	}

	public function getTableNames($parentIdentifier = null)
	{
		$this->notImplemented(__METHOD__);
	}

	public function getTablePrimaryKeyConstraint($tableIdentifier)
	{
		$this->notImplemented(__METHOD__);
	}

	public function getTableForeignKeyConstraints($tableIdentifier)
	{
		$this->notImplemented(__METHOD__);
	}

	public function getTableIndexNames($tableIdentifier)
	{
		$indexes = $this->getTableIndexes($tableIdentifier);
		return Container::map($indexes,
			function ($i, $index) {
				return $index->getName();
			});
	}

	public function getTableIndexes($tableIdentifier)
	{
		$this->notImplemented(__METHOD__);
	}

	public function getTableColumnNames($tableIdentifier)
	{
		$this->notImplemented(__METHOD__);
	}

	public function getTableColumn($tableIdentifier, $columnName)
	{
		$this->notImplemented(__METHOD__);
	}

	public function getViewNames($parentIdentifier = null)
	{
		$this->notImplemented(__METHOD__);
	}

	protected function tryMethod($name, $args, $dflt)
	{
		try
		{
			return \call_user_func_array([
				$this,
				$name
			], $args);
		}
		catch (\Exception $e)
		{
			var_dump(\strval($e->getMessage()));
		}

		return $dflt;
	}

	private function notImplemented($method)
	{
		$cls = TypeDescription::getLocalName($this->getConnection());
		$method = \preg_replace('/.*::(.*)/', '$1', $method);
		throw new \RuntimeException(
			$method . '() not implemented for ' . $cls);
	}
}
