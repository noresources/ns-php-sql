<?php
namespace NoreSources\SQL;

use NoreSources\Container\Container;
use NoreSources\SQL\DBMS\Manager\StructureManager;
use NoreSources\SQL\DBMS\Reference\ReferenceConnection;
use NoreSources\SQL\DBMS\Reference\ReferencePlatform;
use NoreSources\SQL\Structure\NamespaceStructure;
use NoreSources\SQL\Structure\TableStructure;
use NoreSources\SQL\Structure\Comparer\StructureComparer;
use NoreSources\SQL\Syntax\Statement\Structure\CreateTableQuery;
use NoreSources\SQL\Syntax\Statement\Structure\DropNamespaceQuery;
use NoreSources\Test\DatasourceManagerTrait;
use NoreSources\Test\DerivedFileTestTrait;

final class StructureManagerTest extends \PHPUnit\Framework\TestCase
{

	use DerivedFileTestTrait;
	use DatasourceManagerTrait;

	public function __construct($name = null, array $data = [],
		$dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->initializeDatasourceManager(__DIR__);

		$this->initializeDerivedFileTest(__DIR__);
	}

	public function testOperations()
	{
		$connection = new ReferenceConnection();
		$platform = new ReferencePlatform();
		$connection->setPlatform($platform);

		$reference = $this->getDatasource('Company');

		$tests = [
			'renameColumn' => [
				'label' => 'Rename table column',
				'target' => 'Company.renameColumn'
			]
		];

		$comparer = new StructureComparer();

		foreach ($tests as $key => $test)
		{
			$label = Container::keyValue($test, 'label', $key);
			$target = $this->getDatasource($test['target']);
			$manager = new StructureManager($connection);

			$differences = $comparer->compare($reference, $target);
			$txt = Container::implodeValues($differences, PHP_EOL) .
				PHP_EOL;
			$this->assertDerivedFile($txt, __METHOD__, $key,
				'differences');

			$operations = $manager->getModificationOperations(
				$reference, $target);

			$txt = Container::implodeValues($operations, PHP_EOL) .
				PHP_EOL;
			$this->assertDerivedFile($txt, __METHOD__, $key,
				'operations');
		}
	}

	public function testOperationClassname()
	{
		$tests = [
			'Create Table' => [
				'structure' => new TableStructure('Foo'),
				'operation' => 'Create',
				'expected' => CreateTableQuery::class
			],
			'Drop namespace' => [
				'structure' => new NamespaceStructure('Meta'),
				'operation' => 'Drop',
				'expected' => DropNamespaceQuery::class
			] /*,
			   'Table constraint' => [
			   'structure' => new ForeignKeyTableConstraint('Foo'),
			   'operation' => 'Create',
			   'expected' => CreateTableConstraintQuery::class
			   ]*/
		];

		foreach ($tests as $label => $test)
		{
			$structure = $test['structure'];
			$operation = $test['operation'];
			$expected = $test['expected'];

			$actual = StructureManager::getOperationStatementClassname(
				$operation, $structure);
			$this->assertEquals($expected, $actual, $label);
		}
	}
}