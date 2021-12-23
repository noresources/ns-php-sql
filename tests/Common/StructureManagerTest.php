<?php
namespace NoreSources\SQL\Test\Common;

use NoreSources\Container\Container;
use NoreSources\SQL\DBMS\ConnectionInterface;
use NoreSources\SQL\DBMS\Manager\StructureManager;
use NoreSources\SQL\DBMS\PDO\PDOConnection;
use NoreSources\SQL\Structure\Identifier;
use NoreSources\SQL\Structure\NamespaceStructure;
use NoreSources\SQL\Structure\StructureElementContainerInterface;
use NoreSources\SQL\Structure\StructureElementInterface;
use NoreSources\SQL\Structure\StructureSerializerFactory;
use NoreSources\SQL\Structure\TableStructure;
use NoreSources\SQL\Structure\Comparer\StructureComparer;
use NoreSources\Test\DatasourceManager;
use NoreSources\Test\DerivedFileTestTrait;
use NoreSources\Test\SqlFormatter;
use NoreSources\Test\UnittestConnectionManagerTrait;
use NoreSources\Test\UnittestStructureComparerTrait;
use PHPUnit\Framework\TestCase;

final class StructureManagerTest extends TestCase
{
	use UnittestConnectionManagerTrait;
	use DerivedFileTestTrait;
	use UnittestStructureComparerTrait;

	const NAMESPACE_NAME = 'ns_unittests';

	public function __construct($name = null, array $data = [],
		$dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->initializeDerivedFileTest(__DIR__ . '/..');
		$this->structures = new DatasourceManager();
	}

	public function testManager()
	{
		foreach ([
			'Company' => 'Company.renameColumn'
		] as $from => $to)
		{
			$this->runConnectionTest(__METHOD__,
				function (ConnectionInterface $c) {
					return !($c instanceof PDOConnection);
				}, [
					$from,
					$to
				]);
			$this->structures->get($from, true);
			$this->structures->get($to, true);
		}
	}

	private function dbmsManager(ConnectionInterface $connection,
		$dbmsName, $method, $from, $to)
	{
		$suffix = $from . '-' . $to;
		$label = $method . '-' . $suffix;
		$this->assertTrue(true);

		$manager = new StructureManager($connection);
		$reference = $this->structures->get($from, true);
		/** @var NamespaceStructure $referenceNamespace */
		$referenceNamespace = $reference[self::NAMESPACE_NAME];
		$target = $this->structures->get($to, true);

		/*
		 * Drop everything in the namespace first
		 */
		$this->dbmsManagerDropCascade($dbmsName, $method, $manager,
			$from, true);

		{
			$explorer = $connection->getStructureExplorer();
			$tables = $explorer->getTableNames(self::NAMESPACE_NAME);
			$this->assertCount(0, $tables,
				$dbmsName . ' ' . $suffix . ' All table dropped');
		}

		/*
		 * Re-create the whole namespace
		 */
		$this->dbmsManagerCreate($manager, $dbmsName, $method, $from);
		{
			$explorer = $connection->getStructureExplorer();
			$afterCreate = $explorer->getStructure();

			$serializer = StructureSerializerFactory::getInstance();
			$derivedFilePath = $this->getDerivedFilename($method,
				$dbmsName . '_create_' . $from, 'xml');
			$serializer->structureToFile($afterCreate, $derivedFilePath);

			$tables = $explorer->getTableNames(self::NAMESPACE_NAME);
			foreach ($referenceNamespace->getChildElements(
				TableStructure::class) as $t)
			{
				$this->assertContains($t->getName(), $tables,
					$dbmsName . ' ' . $from . ' table ' . $t->getName() .
					' has been created');
			}
		}

		/*
		 * Report statements to required to drop everything again
		 */
		$this->dbmsManagerDropCascade($dbmsName, $method, $manager,
			$from, false);

		/*
		 *  Write modify operations
		 */
		$this->dbmsManagerModify($dbmsName, $method, $manager, $from,
			$to, false);

		/*
		 *  Apply
		 */

		// TEST
		{
			/**
			 * This part is not testable yet
			 */

			return false;
		}

		$this->dbmsManagerModify($dbmsName, $method, $manager, $from,
			$to, true);
	}

	private function dbmsManagerCreate(StructureManager $manager,
		$dbmsName, $method, $from)
	{
		$referenceStructure = $this->structures->get($from, true);

		$statements = $manager->getCreationStatements(
			$referenceStructure);

		$sql = '';
		foreach ($statements as $statement)
			$sql .= SqlFormatter::format($statement) . ';' . PHP_EOL;

		$this->assertDerivedFile($sql, $method,
			$dbmsName . '_create_' . $from, 'sql');

		$manager->create($referenceStructure[self::NAMESPACE_NAME],
			function ($s, $e) use ($dbmsName) {
				return true;
			});
	}

	private function dbmsManagerDropCascade($dbmsName, $method,
		StructureManager $manager, $from, $execute = true)
	{
		$referenceStructure = $this->structures->get($from, true);

		$identifier = Identifier::make([
			self::NAMESPACE_NAME
		]);

		if ($execute)
		{
			return $manager->dropChildren($identifier);
		}

		$parts = $identifier->getPathParts();
		$targetClass = new \ReflectionClass(
			\get_class($referenceStructure));
		$targetStructure = $targetClass->newInstance(
			$referenceStructure->getName());
		while (Container::count($parts))
		{
			$name = \array_shift($parts);
			$this->assertInstanceOf(
				StructureElementContainerInterface::class,
				$referenceStructure);
			$this->assertTrue($referenceStructure->has($name));
			$referenceStructure = $referenceStructure[$name];
			$targetClass = new \ReflectionClass(
				\get_class($referenceStructure));
			$t = $targetClass->newInstance(
				$referenceStructure->getName());
			$targetStructure->appendElement($t);
			$targetStructure = $t;
		}

		$this->dbmsManagerReportOperations($manager, $dbmsName, $method,
			$referenceStructure, $targetStructure, '_drop_' . $from);
	}

	private function dbmsManagerModify($dbmsName, $method,
		StructureManager $manager, $from, $to, $execute = true)
	{
		$target = $this->structures->get($to, true);
		$parts = [
			self::NAMESPACE_NAME
		];
		if ($execute)
			return $manager->modify($target, $parts);

		$reference = null;
		if (true)
			$reference = $manager->getConnection()
				->getStructureExplorer()
				->getStructure();
		else
			$reference = $manager->getStructure();

		$reference = $reference[self::NAMESPACE_NAME];
		$target = $target[self::NAMESPACE_NAME];
		$suffix = '_modify_' . $from . '-' . $to;
		$this->dbmsManagerReportOperations($manager, $dbmsName, $method,
			$reference, $target, $suffix);
	}

	private function dbmsManagerReportOperations(
		StructureManager $manager, $dbmsName, $method,
		StructureElementInterface $reference,
		StructureElementInterface $target, $suffix, $verbose = false)
	{
		// if (false
		{
			$comparer = new StructureComparer();
			$differences = $comparer->compare($reference, $target);
			$txt = $this->stringifyStructureComparison($differences,
				true);
			$this->assertDerivedFile($txt, $method, $dbmsName . $suffix,
				'differences');
			$differences = $manager->filterIgnorableDifferences(
				$differences);
			$txt = $this->stringifyStructureComparison($differences,
				true);
			$this->assertDerivedFile($txt, $method, $dbmsName . $suffix,
				'differences.filtered');
		}

		$operations = $manager->getModificationOperations($reference,
			$target);

		if ($verbose)
			var_dump(Container::implodeValues($operations, PHP_EOL));

		$txt = Container::implodeValues($operations, PHP_EOL) . PHP_EOL;
		$this->assertDerivedFile($txt, $method, $dbmsName . $suffix,
			'operations');

		$statements = $manager->getModificationStatements($operations);

		$sql = '';
		foreach ($statements as $statement)
			$sql .= SqlFormatter::format($statement) . ';' . PHP_EOL;

		$this->assertDerivedFile($sql, $method, $dbmsName . $suffix,
			'sql');

		if ($verbose)
			var_dump($sql);
	}

	/** @var DatasourceManager */
	private $structures;
}
