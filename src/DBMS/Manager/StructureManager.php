<?php

/**
 * Copyright © 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\Manager;

use NoreSources\Container;
use NoreSources\TypeDescription;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Environment;
use NoreSources\SQL\DBMS\ConnectionException;
use NoreSources\SQL\DBMS\ConnectionInterface;
use NoreSources\SQL\DBMS\ConnectionProviderInterface;
use NoreSources\SQL\DBMS\TransactionInterface;
use NoreSources\SQL\DBMS\Configuration\ConfigurationNotAvailableException;
use NoreSources\SQL\DBMS\Configuration\ConfiguratorInterface;
use NoreSources\SQL\DBMS\Configuration\ConfiguratorProviderInterface;
use NoreSources\SQL\DBMS\Explorer\StructureExplorerProviderInterface;
use NoreSources\SQL\DBMS\Traits\ConnectionProviderTrait;
use NoreSources\SQL\Structure\ColumnStructure;
use NoreSources\SQL\Structure\DatasourceStructure;
use NoreSources\SQL\Structure\Identifier;
use NoreSources\SQL\Structure\IndexStructure;
use NoreSources\SQL\Structure\NamespaceStructure;
use NoreSources\SQL\Structure\Structure;
use NoreSources\SQL\Structure\StructureElementContainerInterface;
use NoreSources\SQL\Structure\StructureElementInterface;
use NoreSources\SQL\Structure\StructureException;
use NoreSources\SQL\Structure\StructureResolver;
use NoreSources\SQL\Structure\TableConstraintInterface;
use NoreSources\SQL\Structure\TableStructure;
use NoreSources\SQL\Structure\Comparer\StructureComparer;
use NoreSources\SQL\Structure\Comparer\StructureDifference;
use NoreSources\SQL\Syntax\Statement\Statement;
use NoreSources\SQL\Syntax\Statement\StatementBuilder;
use NoreSources\SQL\Syntax\Statement\Manipulation\InsertQuery;
use NoreSources\SQL\Syntax\Statement\Manipulation\UpdateQuery;
use NoreSources\SQL\Syntax\Statement\Query\SelectQuery;
use NoreSources\SQL\Syntax\Statement\Structure\CreateIndexQuery;
use NoreSources\SQL\Syntax\Statement\Structure\CreateNamespaceQuery;
use NoreSources\SQL\Syntax\Statement\Structure\CreateTableQuery;
use NoreSources\SQL\Syntax\Statement\Structure\StructureOperationQueryInterface;

class StructureManager implements ConnectionProviderInterface
{
	use ConnectionProviderTrait;

	public function __construct(ConnectionInterface $connection)
	{
		$this->setConnection($connection);
	}

	public function getStructure()
	{
		$connection = $this->getConnection();
		if (!isset($this->structure))
		{
			if (!($connection instanceof StructureExplorerProviderInterface))
			{
				throw new \Exception(
					TypeDescription::getName($connection) .
					' does not provide StructureExplorer');
			}
			$explorer = $this->getConnection()->getStructureExplorer();
			$this->structure = $explorer->getStructure();
		}

		return $this->structure;
	}

	/**
	 *
	 * @param StructureElementInterface $element
	 *        	Element to create
	 * @return PreparedStatementInterface[] A list of prepared statement
	 *         to create the given element and all its children.
	 */
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

	/**
	 * Create the given element and its children
	 *
	 * @param StructureElementInterface $element
	 *        	Asset to create
	 * @param callable|NULL $onError
	 *        	A callable that will be invoked on each
	 *        	execution failure. The callable must accept two arguments (the element and the
	 *        	Exception)
	 *        	and return a boolean to indicate if the process should continue or not.
	 * @throws ConnectionException
	 */
	public function create(StructureElementInterface $element,
		$onError = null)
	{
		$statements = $this->getCreationStatements($element);

		if (Container::count($statements) == 0)
			return;

		$connection = $this->getConnection();

		$environment = new Environment($this->getConnection());

		if ($connection instanceof TransactionInterface)
		{
			if (false) // DDM instructions produces implicit commints
				$transaction = $this->getConnection()->newTransactionBlock(
					self::transactionSavePoint(__METHOD__));
		}

		$this->toggleKeyConstraints(false);

		foreach ($statements as $statement)
		{
			try
			{
				$environment->executeStatement($statement);
			}
			catch (\Exception $e)
			{
				$kontinue = false;

				if (\is_callable($onError))
					$kontinue = \call_user_func($onError, $element, $e);

				if (!$kontinue)
				{
					if (isset($transaction))
						$transaction->rollback();
					$this->toggleKeyConstraints(null);
					throw $e;
				}
			}
		}

		$this->toggleKeyConstraints(null);

		if (isset($transaction))
			$transaction->commit();
	}

	/**
	 *
	 * @param StructureElementInterface $target
	 * @param Identifier $subTree
	 */
	public function modify(StructureElementInterface $target,
		$subTree = null)
	{
		$reference = $this->getStructure();
		$target = clone $target;
		$connection = $this->getConnection();

		/** @var Identifier $t */
		$t = Identifier::make($subTree);

		$subTreeParts = $t->getPathParts();
		while (Container::count($subTreeParts))
		{
			$n = \array_shift($subTreeParts);
			if ($reference instanceof StructureElementContainerInterface &&
				$target instanceof StructureElementContainerInterface &&
				$reference->has($n) && $target->has($n))
			{
				$reference = $reference->get($n);
				$target = $target->get($n);
			}
			else
				break;
		}

		if (Container::count($subTreeParts))
			throw new \InvalidArgumentException(
				$subTree .
				' cannot be found in either existing or target structure');

		$operations = $this->getModificationOperations($reference,
			$target);

		if (Container::count($operations) == 0)
			return;

		$statements = $this->getModificationStatements($operations);

		if (Container::count($statements) == 0)
			return;

		$environment = new Environment($connection);

		if ($connection instanceof TransactionInterface)
		{
			if (false) // DDM instructions produces implicit commints
				$transaction = $this->getConnection()->newTransactionBlock(
					self::transactionSavePoint(__METHOD__));
		}

		$this->toggleKeyConstraints(false);

		foreach ($statements as $statement)
		{
			try
			{
				$environment->executeStatement($statement);
			}
			catch (\Exception $e)
			{
				if (isset($transaction))
					$transaction->rollback();
				$this->toggleKeyConstraints(null);
				throw $e;
			}
		}

		$this->toggleKeyConstraints(null);

		if (isset($transaction))
			$transaction->commit();

		unset($this->structure);
	}

	/**
	 *
	 * @param Identifier $identifier
	 * @throws StructureException
	 */
	public function dropChildren($identifier)
	{
		/** @var Identifier $identifier */
		$identifier = Identifier::make($identifier);

		$parts = $identifier->getPathParts();
		$referenceStructure = $this->getStructure();
		$targetClass = new \ReflectionClass(
			\get_class($referenceStructure));
		$rootTargetStructure = $targetStructure = $targetClass->newInstance(
			$referenceStructure->getName());
		while (Container::count($parts))
		{
			$name = \array_shift($parts);
			if (!($referenceStructure instanceof StructureElementContainerInterface &&
				$referenceStructure->has($name)))
				throw new StructureException($name . ' not found',
					$referenceStructure);
			$referenceStructure = $referenceStructure->get($name);
			$targetClass = new \ReflectionClass(
				\get_class($referenceStructure));
			$t = $targetClass->newInstance(
				$referenceStructure->getName());
			$targetStructure->appendElement($t);
			$targetStructure = $t;
		}

		return $this->modify($rootTargetStructure, $identifier);
	}

	/**
	 *
	 * @param StructureOperation[] $operations
	 * @return PreparedStatementInterface[]
	 */
	public function getModificationStatements($operations)
	{
		$statements = [];

		static $prefixes = [
			StructureOperation::ALTER => 'Alter',
			StructureOperation::CREATE => 'Create',
			StructureOperation::DROP => 'Drop',
			StructureOperation::RENAME => 'Rename'
		];

		foreach ($operations as $operation)
		{
			$type = $operation->getType();
			$structure = $operation->getStructure();
			switch ($type)
			{
				case StructureOperation::REPLACE:
					$target = $operation->getTarget();
					// Create

					$temporaryTargetName = $target->getName() .
						'_replace_tmp';
					$temporaryTarget = Structure::duplicate($target,
						$temporaryTargetName);

					$createTemporary = $this->getOperationStatement(
						'Create', $temporaryTarget);

					$statements[] = $createTemporary;

					if (Structure::hasData($target))
					{
						// Copy ref to temporary target

						$c = $temporaryTarget->getChildElements(
							ColumnStructure::class);

						$statements[] = $this->getDataCopyStatements(
							$operation->getReference(), $temporaryTarget);
					}
					// Drop ref
					$dropReference = $this->getOperationStatement(
						'Drop', $operation->getReference());
					$statements[] = $dropReference;

					// Create target
					$createTarget = $this->getOperationStatement(
						'Create', $target);
					$statements[] = $createTarget;

					if (Structure::hasData($target))
					{
						// Copy temporary to target
						$statements[] = $this->getDataCopyStatements(
							$temporaryTarget, $target);
					}
					// Drop temporary
					$dropTemporary = $this->getOperationStatement(
						'Drop', $temporaryTarget);

					$statements[] = $dropTemporary;

				break;
				default: // Drop, Create
					$prefix = $prefixes[$type];
					$statement = $this->getOperationStatement($prefix,
						$structure);
					if ($type == StructureOperation::RENAME)
					{}
					elseif ($type == StructureOperation::ALTER)
					{}
					$statements[] = $statement;
				break;
			}
		}

		$builder = StatementBuilder::getInstance();
		$platform = $this->getConnection()->getPlatform();

		return Container::map($statements,
			function ($k, $statement) use ($builder, $platform) {
				return $builder($statement, $platform);
			});
	}

	public function getModificationOperations(
		StructureElementInterface $reference,
		StructureElementInterface $target)
	{
		$comparer = StructureComparer::getInstance();
		$differences = $comparer->compare($reference, $target);

		$operations = [];

		// Filter renames to nothing
		$differences = Container::filter($differences,
			function ($k, $difference) {
				/** @var StructureDifference $difference */
				if ($difference->getType() ==
				StructureDifference::RENAMED)
					return !empty($difference->getTarget()->getName());
				return true;
			});

		$newDifferences = [];
		$platform = $this->getConnection()->getPlatform();
		$referenceResolver = new StructureResolver($reference);
		$targetResolver = new StructureResolver($target);
		$phase = 0;
		do
		{
			$phase++;
			$newDifferences = [];
			foreach ($differences as $difference)
			{

				/** @var StructureDifference $difference */
				$classname = self::getDifferenceRequiredStatementClassname(
					$difference);

				$exists = $platform->hasStatement($classname);

				if ($exists)
				{
					$operations[] = StructureOperation::createFromDifference(
						$difference);
					continue;
				}

				$structure = $difference->getStructure();
				$hasData = Structure::hasData($structure);
				$type = $difference->getType();

				if ((($type == StructureDifference::ALTERED) ||
					($type == StructureDifference::RENAMED)) && !$hasData)
				{
					$newDifferences[] = new StructureDifference(
						StructureDifference::DROPPED,
						$difference->getReference());
					$newDifferences[] = new StructureDifference(
						StructureDifference::CREATED,
						$difference->getTarget());
					continue;
				}

				// Otherwise, alter parent

				$r = null;
				$t = null;
				$parentIdentifier = $structure->getIdentifier()->getParentIdentifier();
				if (empty(\strval($parentIdentifier)))
					throw new \Exception('EMPTY ' . $difference);

				if ($structure instanceof ColumnStructure ||
					$structure instanceof TableConstraintInterface)
				{
					$r = $referenceResolver->findTable(
						$parentIdentifier);
					$t = $targetResolver->findTable($parentIdentifier);
				}
				else
					throw new \Exception(
						'No statement available for ' .
						$difference->getType() . ' ' .
						TypeDescription::getLocalName(
							$difference->getStructure()));

				$operations[] = new StructureOperation(
					StructureOperation::REPLACE, $r, $t);
			} // each diff

			$differences = \array_unique($newDifferences);
		}
		while (Container::count($differences));

		$operations = \array_unique($operations);

		usort($operations, function ($a, $b) {
			return $a->compare($b);
		});

		return $operations;
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

	protected function getDataCopyStatements(
		StructureElementInterface $from, StructureElementInterface $to)
	{
		$platform = $this->getConnection()->getPlatform();
		if ($from instanceof ColumnStructure)
		{
			/**
			 *
			 * @var UpdateQuery $update
			 */
			$update = $platform->newStatement(UpdateQuery::class);
			$update->table($from->getParentElement());
			$update($from->getName(), $to->getName());
			return $update;
		}
		elseif ($from instanceof TableStructure)
		{
			/** @var InsertQuery */
			$insert = $platform->newStatement(InsertQuery::class);
			/** @var SelectQuery */
			$select = $platform->newStatement(SelectQuery::class);

			$comparer = StructureCOmparer::getInstance();
			$columnNames = Container::map(
				$from->getChildElements(ColumnStructure::class),
				function ($k, $c) {
					return $c->getName();
				});
			$changedColumnNames = [];
			$differences = $comparer->compare($from, $to);

			foreach ($differences as $difference)
			{
				if (!($difference->getReference() instanceof ColumnStructure))
					continue;
				/** @var StructureDifference $difference */
				switch ($difference->getType())
				{
					case StructureDifference::DROPPED:
						$changedColumnNames[] = $difference->getReference()->getName();
					break;
					case StructureDifference::RENAMED:
						$select->columns(
							$difference->getReference()
								->getName());
						$insert->columns(
							$difference->getTarget()
								->getName());
						$changedColumnNames[] = $difference->getReference()->getName();
					break;
				}
			}

			foreach ($columnNames as $name)
			{
				if (\in_array($name, $changedColumnNames))
					continue;
				$insert->columns($name);
				$select->columns($name);
			}

			$insert->into($to);
			$select->from($from);
			$insert->select($select);

			return $insert;
		}

		throw new \InvalidArgumentException(
			TypeDescription::getName($from) . ' not supported');
	}

	protected function getNamespaceStructureCreationStatements(
		NamespaceStructure $ns)
	{
		$platform = $this->getConnection()->getPlatform();
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
		$platform = $this->getConnection()->getPlatform();
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
		$platform = $this->getConnection()->getPlatform();

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
	 * @param unknown $prefix
	 * @param StructureElementInterface $structure
	 * @return StructureOperationQueryInterface|Statement
	 */
	protected function getOperationStatement($prefix,
		StructureElementInterface $structure)
	{
		$classname = self::getOperationStatementClassname($prefix,
			$structure);
		$statement = $this->getConnection()
			->getPlatform()
			->newStatement($classname);
		if ($statement instanceof StructureOperationQueryInterface)
			$statement->forStructure($structure);
		return $statement;
	}

	protected static function getOperationStatementClassname($prefix,
		StructureElementInterface $structure)
	{
		$localName = $prefix .
			\preg_replace('/Structure$/', '',
				TypeDescription::getLocalName($structure)) . 'Query';
		$name = TypeDescription::getNamespaces(CreateTableQuery::class,
			true);
		array_push($name, $localName);
		return \implode('\\', $name);
	}

	protected static function getDifferenceRequiredStatementClassname(
		StructureDifference $difference)
	{
		static $operationMap = [
			StructureDifference::ALTERED => 'Alter',
			StructureDifference::CREATED => 'Create',
			StructureDifference::DROPPED => 'Drop',
			StructureDifference::RENAMED => 'Rename'
		];
		$structure = $difference->getStructure();
		return self::getOperationStatementClassname(
			$operationMap[$difference->getType()], $structure);
	}

	protected function toggleKeyConstraints($value)
	{
		$connection = $this->getConnection();
		$platform = $connection->getPlatform();
		$configurator = null;
		if ($connection instanceof ConfiguratorProviderInterface)
		{
			$configurator = $connection->getConfigurator();
		}
		elseif ($platform instanceof ConfigurationNotAvailableException)
		{
			$configurator = $platform->getConfigurator();
		}

		if ($configurator instanceof ConfiguratorInterface &&
			$configurator->has(K::CONFIGURATION_KEY_CONSTRAINTS))
		{
			if ($value === null)
				$configurator->offsetUnset(
					K::CONFIGURATION_KEY_CONSTRAINTS);
			else
				$configurator[K::CONFIGURATION_KEY_CONSTRAINTS] = $value;
		}
	}

	protected static function transactionSavePoint($method)
	{
		return 'nsphpsql_structure_' .
			\preg_replace('/.*:(.*)/', '$1', $method);
	}

	/**
	 *
	 *
	 * /**
	 *
	 * @var DatasourceStructure
	 */
	private $structure;
}
