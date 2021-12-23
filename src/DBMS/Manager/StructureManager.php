<?php

/**
 * Copyright © 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\Manager;

use NoreSources\Container\Container;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Environment;
use NoreSources\SQL\DBMS\ConnectionException;
use NoreSources\SQL\DBMS\ConnectionInterface;
use NoreSources\SQL\DBMS\ConnectionProviderInterface;
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
use NoreSources\SQL\Structure\Comparer\DifferenceExtra;
use NoreSources\SQL\Structure\Comparer\StructureComparer;
use NoreSources\SQL\Structure\Comparer\StructureComparison;
use NoreSources\SQL\Structure\Inspector\StructureInspector;
use NoreSources\SQL\Syntax\Statement\Statement;
use NoreSources\SQL\Syntax\Statement\StatementBuilder;
use NoreSources\SQL\Syntax\Statement\Manipulation\InsertQuery;
use NoreSources\SQL\Syntax\Statement\Manipulation\UpdateQuery;
use NoreSources\SQL\Syntax\Statement\Query\SelectQuery;
use NoreSources\SQL\Syntax\Statement\Structure\CreateIndexQuery;
use NoreSources\SQL\Syntax\Statement\Structure\CreateNamespaceQuery;
use NoreSources\SQL\Syntax\Statement\Structure\CreateTableQuery;
use NoreSources\SQL\Syntax\Statement\Structure\StructureOperationQueryInterface;
use NoreSources\Type\TypeDescription;

class StructureManager implements ConnectionProviderInterface
{
	use ConnectionProviderTrait;

	public function __construct(ConnectionInterface $connection)
	{
		$this->setConnection($connection);
	}

	/**
	 * Get the currently known datasource structure.
	 *
	 * @throws \Exception
	 * @return DatasourceStructure
	 */
	public function getStructure()
	{
		if (!isset($this->structure))
		{
			$connection = $this->getConnection();
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

		$environment = new Environment($connection);

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
					$this->invalidate();

					$this->toggleKeyConstraints(null);
					throw $e;
				}
			}
		}

		$this->toggleKeyConstraints(null);
		$this->invalidate();
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

		if (isset($subTree))
		{
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
		}

		$operations = $this->getModificationOperations($reference,
			$target);

		if (Container::count($operations) == 0)
			return;

		$statements = $this->getModificationStatements($operations);

		if (Container::count($statements) == 0)
			return;

		$environment = new Environment($connection);

		$this->toggleKeyConstraints(false);

		foreach ($statements as $statement)
		{
			try
			{
				$environment->executeStatement($statement);
			}
			catch (\Exception $e)
			{
				$this->toggleKeyConstraints(null);
				throw $e;
			}
		}

		$this->toggleKeyConstraints(null);

		$this->invalidate();
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
	 * Invalidate datasource structure cache
	 */
	public function invalidate()
	{
		unset($this->structure);
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
				case StructureOperation::BACKUP:
					$statements[] = $this->getOperationStatement(
						'Create', $operation->getTarget());
					$statements[] = $this->getDataCopyStatements(
						$operation->getReference(),
						$operation->getTarget());
				break;
				case StructureOperation::RESTORE:
					$statements[] = $this->getOperationStatement(
						'Create', $operation->getTarget());
					$statements[] = $this->getDataCopyStatements(
						$operation->getReference(),
						$operation->getTarget());
					$statements[] = $this->getOperationStatement('Drop',
						$operation->getReference());
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

	/**
	 *
	 * @param StructureComparison[] $comparisons
	 * @return array
	 */
	public function filterIgnorableDifferences($comparisons)
	{
		$renames = self::getRenamedStructureElements($comparisons);
		return Container::filterValues($comparisons,
			function ($v) use ($renames) {
				if (($v->getType() &
				StructureComparison::DIFFERENCE_TYPES) == 0)
					return false;

				if (($v->getType() & StructureComparison::ALTERED) == 0)
					return true;

				if ($this->canIgnoreAlteredStructureDifference($v,
					$renames))
					return false;
				return true;
			});
	}

	public function getModificationOperations(
		StructureElementInterface $reference,
		StructureElementInterface $target)
	{
		$comparer = StructureComparer::getInstance();
		$comparisons = $comparer->compare($reference, $target,
			StructureComparison::ALL_TYPES);
		$differences = $this->filterIgnorableDifferences($comparisons);

		$operations = [];

		// Filter renames to nothing
		$differences = Container::filter($differences,
			function ($k, $difference) {
				/** @var StructureComparison $difference */
				if ($difference->getType() ==
				StructureComparison::RENAMED)
					return !empty($difference->getTarget()->getName());
				return true;
			});

		$newDifferences = [];
		$platform = $this->getConnection()->getPlatform();
		$referenceResolver = new StructureResolver($reference);
		$targetResolver = new StructureResolver($target);

		do
		{
			$newDifferences = [];
			foreach ($differences as $difference)
			{
				/** @var StructureComparison $difference */
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
				$hasData = StructureInspector::getInstance()->hasData(
					$structure);
				$type = $difference->getType();

				if (!$hasData)
				{
					if ((($type == StructureComparison::ALTERED) ||
						($type == StructureComparison::RENAMED)))
					{
						$newDifferences[] = new StructureComparison(
							StructureComparison::DROPPED,
							$difference->getReference());
						$newDifferences[] = new StructureComparison(
							StructureComparison::CREATED, null,
							$difference->getTarget());
						continue;
					}
				}

				// Otherwise, alter parent

				$r = null;
				$t = null;
				$parentIdentifier = null;
				$p = $structure->getParentElement();
				if ($p)
					$parentIdentifier = $p->getIdentifier();
				else
					$parentIdentifier = $structure->getIdentifier()->getParentIdentifier();

				if (empty(\strval($parentIdentifier)))
					throw new \Exception(
						'Cannot alter parent of ' .
						TypeDescription::getLocalName(
							$this->getConnection()) . ' ' .
						TypeDescription::getLocalName($reference) . ' ' .
						\strval($reference->getIdentifier()) .
						': empty parent identifier');

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

				$this->createBackupRestore($operations, $r, $t);
			} // each diff

			$differences = \array_unique($newDifferences);
		}
		while (Container::count($differences));

		/**
		 *
		 * @var StructureOperation[] $operations
		 */
		$operations = \array_unique($operations);

		$inspector = new StructureInspector();
		$changes = 0;
		do
		{
			$changes = 0;
			$newOperations = [];
			foreach ($operations as $operation)
			{
				$newOperations[] = $operation;
				if (!$operation->getType() != StructureOperation::DROP)
					continue;

				$reverseReferences = $inspector->getReverseReferenceMap(
					$operation->getReference());
				foreach ($reverseReferences as $rr)
				{
					if ($this->isDropped($operations, $rr))
						continue;

					$target = $this->findTarget($comparison, $rr);
					$this->createBackupRestore($newOperations, $rr,
						$target);
				}
			}

			$operations = $newOperations;
		}
		while ($changes);

		usort($operations,
			function ($a, $b) {
				$c = $a->compare($b);
				return $c;
			});

		return $operations;
	}

	public function getChildrenCreationStatements(
		StructureElementContainerInterface $container)
	{
		$children = $container->getChildElements();
		uasort($children,
			[
				StructureInspector::getInstance(),
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
	 * @param StructureOperation[] $operations
	 *        	Operation array
	 * @param StructureElementInterface $element
	 *        	Element to backup/restore
	 * @param StructureElementInterface $target
	 *        	Element counterpart in the target structure
	 */
	protected function createBackupRestore(&$operations,
		StructureElementInterface $element,
		StructureElementInterface $target = null)
	{
		$hasData = StructureInspector::getInstance()->hasData($element);
		$name = $element->getName();
		$backup = null;
		$backupStructure = null;
		if (empty($name))
			$name = $element->getIdentifier();
		if ($hasData)
		{
			$backupName = $name . '_backup';
			$backupStructure = Structure::duplicate($element,
				$backupName);
			if ($backupStructure instanceof TableStructure)
			{
				$this->cleanupBackupTable($backupStructure);
			}
			$target->getParentElement()->appendElement($backupStructure);
			$operations[] = $backup = new StructureOperation(
				StructureOPeration::BACKUP, $element, $backupStructure);
		}
		$operations[] = $drop = new StructureOperation(
			StructureOperation::DROP, $element);
		if ($hasData)
		{
			$operations[] = $restore = new StructureOperation(
				StructureOperation::RESTORE, $backupStructure, $target);

			$drop->insertAfter($backup);
			$restore->insertAfter($drop);
		}
		else
		{
			$operations[] = new StructureOperation(
				StructureOperation::CREATE, $target);
		}
	}

	/**
	 *
	 * @param StructureComparison[] $comparisons
	 * @param StructureElementInterface $reference
	 */
	protected function findTarget($comparisons,
		StructureElementInterface $reference)
	{
		foreach ($comparisons as $c)
		{
			if ($c->getReference() === $reference && $c->getTarget())
				return $c->getTarget();
		}

		return null;
	}

	/**
	 *
	 * @param StructureOperation[] $operations
	 * @param StructureElementInterface $element
	 * @return boolean
	 */
	protected function isDropped($operations,
		StructureElementInterface $element)
	{
		foreach ($operations as $operation)
		{
			if ($operation->getType() == StructureOperation::DROP &&
				$operation->getReference() === $element)
				return true;
		}
		return false;
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
				/** @var StructureComparison $difference */
				switch ($difference->getType())
				{
					case StructureComparison::DROPPED:
						$changedColumnNames[] = $difference->getReference()->getName();
					break;
					case StructureComparison::RENAMED:
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
	public function getOperationStatement($prefix,
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

	public static function getOperationStatementClassname($prefix,
		StructureElementInterface $structure)
	{
		$referenceClass = new \ReflectionClass(CreateTableQuery::class);
		$ns = $referenceClass->getNamespaceName();
		$cls = new \ReflectionClass(\get_class($structure));
		do
		{
			$localName = $cls->getName();

			$localName = $prefix .
				\preg_replace('/Interface$/', '',
					\preg_replace('/Structure$/', '',
						$cls->getShortName())) . 'Query';

			$classname = $ns . '\\' . $localName;
			if (\class_exists($classname))
				return $classname;

			if ($cls->getParentClass())
				$clas = $cls->getParentClass();
			else
			{
				$interfaces = Container::filterValues(
					$cls->getInterfaces(),
					function ($itf) {
						/** @var \ReflectionClass  $itf */
						return $itf->implementsInterface(
							StructureElementInterface::class);
					});
				$cls = null;
				$cls = \array_shift($interfaces);
			}
		}
		while ($cls);

		throw new \Exception(
			'Unable to find ' . $prefix . ' structure query for ' .
			TypeDescription::getName($structure) . '. Class ' .
			$classname . ' does not exists.');
	}

	protected static function getDifferenceRequiredStatementClassname(
		StructureComparison $difference)
	{
		static $operationMap = [
			StructureComparison::ALTERED => 'Alter',
			StructureComparison::CREATED => 'Create',
			StructureComparison::DROPPED => 'Drop',
			StructureComparison::RENAMED => 'Rename'
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

	protected function cleanupBackupTable(TableStructure $table)
	{
		// Remove all constraints
		$constraints = $table->getChildElements(
			TableConstraintInterface::class);
		foreach ($constraints as $key => $constraint)
			$table->offsetUnset($constraint);
		// Remove AUTO INCREMENT flags
		foreach ($table->getChildElements(ColumnStructure::class) as $column)
		{
			/** @var ColumnStructure $column */
			if ($column->has(K::COLUMN_FLAGS))
			{
				$flags = $column->get(K::COLUMN_FLAGS);
				$column->setColumnProperty(K::COLUMN_FLAGS,
					($flags & ~K::COLUMN_FLAG_AUTO_INCREMENT));
			}
		}
	}

	/**
	 *
	 * @param StructureComparison $difference
	 *        	ALTERED difference of a non-data element
	 * @param StructureComparison[] $renames
	 *        	Map of RENAMED difference
	 *        	where key is the canonical path of the renamed element
	 * @return boolean
	 */
	public function canIgnoreAlteredStructureDifference(
		StructureComparison $difference, $renames)
	{
		$extras = $difference->getExtras();
		$platform = $this->getConnection()->getPlatform();
		if (Container::count($extras) == 0)
			return false;

		static $expectedTypes = [
			DifferenceExtra::TYPE_COLUMN,
			DifferenceExtra::TYPE_TABLE,
			DifferenceExtra::TYPE_FOREIGN_COLUMN,
			DifferenceExtra::TYPE_FOREIGN_TABLE
		];

		foreach ($extras as $extra)
		{
			$type = Container::keyValue($extra,
				DifferenceExtra::KEY_TYPE);
			if (!Container::valueExists($expectedTypes, $type))
			{
				return false;
			}

			$p = Container::keyValue($extra,
				DifferenceExtra::KEY_PREVIOUS);
			$n = Container::keyValue($extra, DifferenceExtra::KEY_NEW);
			if (!($p && $n))
			{
				return false;
			}

			/** @var StructureComparison $rename */
			$rename = Container::keyValue($renames,
				$p->getIdentifier()->getPath());
			if (!$rename)
				return false;

			if ($n !== $rename->getTarget())
				return false;

			$classname = self::getDifferenceRequiredStatementClassname(
				$rename);

			if (!$platform->hasStatement($classname))
				return false;
		}

		return true;
	}

	/**
	 *
	 * @param StructureComparison[] $differences
	 * @return array
	 */
	public static function getRenamedStructureElements($differences)
	{
		$a = [];
		foreach ($differences as $difference)
		{

			if ($difference->getType() == StructureComparison::RENAMED &&
				StructureInspector::getInstance()->hasData(
					$difference->getReference()))
			{
				$a[$difference->getReference()
					->getIdentifier()
					->getPath()] = $difference;
			}
		}
		return $a;
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
