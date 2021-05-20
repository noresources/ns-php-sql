<?php

/**
 * Copyright © 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS;

use NoreSources\TypeDescription;
use NoreSources\Expression\Identifier;
use NoreSources\SQL\DBMS\SQLite\SQLiteStructureExplorer;
use NoreSources\SQL\Structure\DatasourceStructure;
use NoreSources\SQL\Structure\IndexStructure;
use NoreSources\SQL\Structure\NamespaceStructure;
use NoreSources\SQL\Structure\Structure;
use NoreSources\SQL\Structure\StructureElementContainerInterface;
use NoreSources\SQL\Structure\StructureElementInterface;
use NoreSources\SQL\Structure\StructureResolver;
use NoreSources\SQL\Structure\TableStructure;
use NoreSources\SQL\Syntax\Statement\StatementBuilder;
use NoreSources\SQL\Syntax\Statement\Structure\CreateIndexQuery;
use NoreSources\SQL\Syntax\Statement\Structure\CreateNamespaceQuery;
use NoreSources\SQL\Syntax\Statement\Structure\CreateTableQuery;

class StructureManager
{

	public function __construct(ConnectionInterface $connection)
	{
		$this->connection = $connection;
	}

	public function getStructure()
	{
		if (!isset($this->structure))
		{
			$explorer = new SQLiteStructureExplorer($this->connection);
			$this->structure = $explorer->getStructure();
		}

		return $this->structure;
	}

	public function getCreationStatements(
		StructureElementInterface $element)
	{
		$className = TypeDescription::getLocalName($element);
		$method = 'get' . $className . 'CreationStatements';
		$callable = [
			$this,
			$method
		];

		if (\method_exists($this, $method))
		{
			return \call_user_func([
				$this,
				$method
			], $element);
		}

		if ($element instanceof StructureElementContainerInterface)
		{
			return $this->getChildrenCreationStatements($element);
		}

		return [];
	}

	public function create(StructureElementInterface $element)
	{
		$statements = $this->getCreationStatements($element);
		foreach ($statements as $statement)
			$this->connection->executeStatement($statement);
	}

	public function getChildrenCreationStatements(
		StructureElementContainerInterface $container)
	{
		$children = $container->getChildElements();
		uasort($children, [
			Structure::class,
			'dependencyCompare'
		]);
		$statements = [];
		foreach ($children as $child)
		{
			$statements = \array_merge($statements,
				$this->getCreationStatements($child));
		}

		return $statements;
	}

	/**
	 *
	 * @param StructureElementInterface $target
	 * @param Identifier $subTree
	 */
	public function upgrade(StructureElementInterface $target,
		$subTree = null)
	{
		$subTree = Identifier::make($subTree);
		$reference = $this->getStructure();
	}

	protected function getNamespaceStructureCreationStatements(
		NamespaceStructure $ns)
	{
		$platform = $this->connection->getPlatform();
		$statements = [];
		/**
		 *
		 * @var CreateNamespaceQuery $query
		 */
		$query = $platform->newStatement(CreateNamespaceQuery::class);
		$query->identifier($ns->getIdentifier());

		$resolver = new StructureResolver($ns);
		$builder = StatementBuilder::getInstance();
		$statement = $builder($query, $platform, $resolver);
		$statements = $this->getChildrenCreationStatements($ns);
		\array_unshift($statements, $statement);
		return $statements;
	}

	protected function getTableStructureCreationStatements(
		TableStructure $table)
	{
		$platform = $this->connection->getPlatform();
		$statements = [];

		/**
		 *
		 * @var CreateTableQuery $query
		 */
		$query = $platform->newStatement(CreateTableQuery::class);
		$query->table($table);

		$resolver = new StructureResolver($table);
		$builder = StatementBuilder::getInstance();
		$statements[] = $builder($query, $platform, $resolver);

		$indexes = $table->getChildElements(IndexStructure::class);
		foreach ($indexes as $index)
		{
			$indexStatements = $this->getCreationStatements($index);
			$statements = \array_merge($statements, $indexStatements);
		}

		return $statements;
	}

	protected function getIndexStructureCreationStatements(
		IndexStructure $index)
	{
		$platform = $this->connection->getPlatform();

		/**
		 *
		 * @var CreateIndexQuery $query
		 */
		$query = $platform->newStatement(CreateIndexQuery::class);
		$query->setFromTable($index->getParentElement(),
			$index->getName());

		$resolver = new StructureResolver($index->getParentElement());
		$builder = StatementBuilder::getInstance();
		$statement = $builder($query, $platform, $resolver);
		return [
			$statement
		];
	}

	/**
	 *
	 * @var ConnectionInterface
	 */
	private $connection;

	/**
	 *
	 * @var DatasourceStructure
	 */
	private $structure;
}
