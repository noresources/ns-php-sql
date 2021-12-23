<?php
/**
 * Copyright © 2020 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL;

use NoreSources\Container\Container;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\Explorer\StructureExplorerInterface;
use NoreSources\SQL\DBMS\Manager\StructureManager;
use NoreSources\SQL\DBMS\SQLite\SQLiteConnection;
use NoreSources\SQL\Structure\DatasourceStructure;
use NoreSources\SQL\Structure\NamespaceStructure;
use NoreSources\SQL\Structure\StructureSerializerFactory;
use NoreSources\SQL\Structure\TableStructure;
use NoreSources\SQL\Structure\Comparer\StructureComparer;
use NoreSources\Test\DerivedFileTestTrait;

final class SQLiteStructureManagerTest extends \PHPUnit\Framework\TestCase
{

	use DerivedFileTestTrait;

	public function __construct($name = null, array $data = [],
		$dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->initializeDerivedFileTest(__DIR__ . '/..');
	}

	public function testRenameColumn()
	{
		$environment = new Environment(
			[
				K::CONNECTION_TYPE => SQLiteConnection::class,
				K::CONNECTION_STRUCTURE_FILENAME_FACTORY => function (
					$s, $t) {
					return ''; // TEMP
				}
			]);

		$connection = $environment->getConnection();
		$this->assertInstanceOf(SQLiteConnection::class, $connection,
			'Connection  created');
		$manager = new StructureManager($connection);
		$structureFactoory = StructureSerializerFactory::getInstance();

		$structureFileDirectory = __DIR__ . '/../data/structures';

		$referenceStructureFilename = $structureFileDirectory .
			'/Company.xml';
		$targetStructureFilename = $structureFileDirectory .
			'/Company.renameColumn.xml';

		$referenceStructure = $structureFactoory->structureFromFile(
			$referenceStructureFilename);

		$targetStructure = $structureFactoory->structureFromFile(
			$targetStructureFilename);

		$this->assertInstanceOf(DatasourceStructure::class,
			$referenceStructure);

		$manager->create($referenceStructure);

		/** @var StructureExplorerInterface $explorer */
		$explorer = $environment->getConnection()->getStructureExplorer();

		/** @var DatasourceStructure $structure */
		$dbmsStructure = $explorer->getStructure();

		$comparer = new StructureComparer();
		$differences = $comparer->compare($referenceStructure,
			$dbmsStructure);

		if (true)
			return;

		$this->assertCount(0, $differences,
			'No differences between reference structure and SQLite database' .
			PHP_EOL . \implode(PHP_EOL, $differences));

		$this->assertArrayHasKey('ns_unittests',
			$dbmsStructure->getChildElements());

		/** @var NamespaceStructure $namespace */
		$namespace = $dbmsStructure['ns_unittests'];

		$this->assertArrayHasKey('Employees', $namespace,
			'Employees table was created');

		/** @var TableStructure $employees */
		$employees = $namespace['Employees'];

		foreach ([
			$referenceStructure,
			$dbmsStructure
		] as $reference)
		{
			$operations = $manager->getModificationOperations(
				$reference, $targetStructure);
			$list = Container::implodeValues($operations, PHP_EOL) .
				PHP_EOL;
			$this->assertDerivedFile($list, __METHOD__, null, 'txt');
		}

		$manager->modify($targetStructure);

		$columns = $explorer->getTableColumnNames(
			[
				'ns_unittests',
				'Employees'
			]);

		$this->assertContains('firstName', $columns,
			'Employees table columns (' .
			Container::implodeValues($columns, ', ') .
			') contains "firstName"');
	}
}