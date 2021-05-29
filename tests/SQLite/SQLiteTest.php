<?php
/**
 * Copyright © 2020 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL;

use NoreSources\Container;
use NoreSources\DateTime;
use NoreSources\TypeDescription;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\Explorer\StructureExplorerInterface;
use NoreSources\SQL\DBMS\SQLite\SQLiteConnection;
use NoreSources\SQL\DBMS\SQLite\SQLiteConstants;
use NoreSources\SQL\DBMS\SQLite\SQLitePlatform;
use NoreSources\SQL\DBMS\SQLite\SQLitePreparedStatement;
use NoreSources\SQL\DBMS\SQLite\SQLiteRecordset;
use NoreSources\SQL\Result\InsertionStatementResultInterface;
use NoreSources\SQL\Result\Recordset;
use NoreSources\SQL\Structure\ColumnStructure;
use NoreSources\SQL\Structure\DatasourceStructure;
use NoreSources\SQL\Structure\ForeignKeyTableConstraint;
use NoreSources\SQL\Structure\KeyTableConstraintInterface;
use NoreSources\SQL\Structure\NamespaceStructure;
use NoreSources\SQL\Structure\PrimaryKeyTableConstraint;
use NoreSources\SQL\Structure\TableStructure;
use NoreSources\SQL\Syntax\Data;
use NoreSources\SQL\Syntax\FunctionCall;
use NoreSources\SQL\Syntax\TimestampFormatFunction;
use NoreSources\SQL\Syntax\Statement\Statement;
use NoreSources\SQL\Syntax\Statement\StatementBuilder;
use NoreSources\SQL\Syntax\Statement\StatementData;
use NoreSources\SQL\Syntax\Statement\Manipulation\InsertQuery;
use NoreSources\SQL\Syntax\Statement\Query\SelectQuery;
use NoreSources\SQL\Syntax\Statement\Structure\CreateTableQuery;
use NoreSources\Test\ConnectionHelper;
use NoreSources\Test\DatasourceManager;
use NoreSources\Test\DerivedFileManager;

final class SQLiteTest extends \PHPUnit\Framework\TestCase
{

	public function __construct($name = null, array $data = [],
		$dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->connection = null;
		$this->derivedFileManager = new DerivedFileManager(
			__DIR__ . '/..');
		$this->datasources = new DatasourceManager();
		$this->createdTables = new \ArrayObject();
	}

	public function testStringStatement()
	{
		if (!$this->prerequisites())
			return;
		$environment = new Environment(
			[
				K::CONNECTION_TYPE => SQLiteConnection::class,
				K::CONNECTION_SOURCE => [
					'foo' => __DIR__ . '/../data/Company.sqlite'
				]
			]);

		$this->assertInstanceOf(SQLiteConnection::class,
			$environment->getConnection(), 'Valid connection');

		$sql = 'select name, salary as Money from foo.employees where gender = :g';
		$data = new Statement($sql);
		$data->getParameters()->setParameter(0, 'g', ':g');

		foreach ([
			'SQL' => $sql,
			Statement::class => $data
		] as $label => $statement)
		{

			/**
			 *
			 * @var Recordset $result
			 */
			$result = $environment($statement, [
				'g' => 'M'
			]);

			$this->assertInstanceOf(Recordset::class, $result,
				$label . ' result type');

			$expectedNames = [
				'name',
				'Money'
			];
			$expectedRowCount = 2;

			$this->assertCount(\count($expectedNames),
				$result->getResultColumns(),
				$label . ' number of result columns');

			foreach ($result->getResultColumns() as $index => $column)
			{
				$this->assertEquals($expectedNames[$index],
					$column->getName(),
					$label . ' column #' . $index . ' name');
			}

			$this->assertCount($expectedRowCount, $result,
				$label . ' number of rows');
		}
	}

	public function testUnserialize()
	{
		if (!$this->prerequisites())
			return;
		$structure = $this->datasources->get('types');
		$tableStructure = $structure['ns_unittests']['types'];
		$this->assertInstanceOf(Structure\TableStructure::class,
			$tableStructure);

		$this->assertTrue($this->createDatabase(), 'Create SQLite file');

		$this->assertTrue($this->createTable($tableStructure),
			'Create table ' . \strval($tableStructure->getIdentifier()));

		// Default values
		$statement = new InsertQuery($tableStructure);

		$prepared = ConnectionHelper::prepareStatement(
			$this->connection, $statement, $tableStructure);

		$sql = \SqlFormatter::format(strval(strval($prepared)), false);
		$this->derivedFileManager->assertDerivedFile($sql, __METHOD__,
			'insert', 'sql');

		$result = $this->connection->executeStatement($prepared);
		$this->assertInstanceOf(
			InsertionStatementResultInterface::class, $result);

		$selectStatement = new SelectQuery($tableStructure);
		$selectPrepared = ConnectionHelper::prepareStatement(
			$this->connection, $selectStatement, $tableStructure);

		$result = $this->connection->executeStatement($selectPrepared);
		$this->assertInstanceOf(SQLiteRecordset::class, $result);

		$result->setFlags(
			Recordset::FETCH_ASSOCIATIVE | Recordset::FETCH_UNSERIALIZE);
		$records = $result->getArrayCopy();

		$this->assertCount(1, $records);

		$record = $records[0];

		$this->assertEquals('integer',
			TypeDescription::getName($record['int']));
		$this->assertEquals('double',
			TypeDescription::getName($record['float']));
		$this->assertInstanceOf(\DateTime::class, $record['timestamp']);
		$this->assertEquals('boolean',
			TypeDescription::getName($record['boolean']));
	}

	public function testParametersEmployees()
	{
		if (!$this->prerequisites())
			return;
		$structure = $this->datasources->get('Company');
		$tableStructure = $structure['ns_unittests']['Employees'];
		$this->assertInstanceOf(Structure\TableStructure::class,
			$tableStructure);

		$this->assertTrue($this->createDatabase(), 'Create SQLite file');

		$this->assertTrue($this->createTable($tableStructure),
			'Create table ' . \strval($tableStructure->getIdentifier()));

		$statement = new InsertQuery($tableStructure);
		$statement['gender'] = 'M';
		$statement('name', ':nameValue');
		$statement('salary', ':salaryValue');

		$prepared = ConnectionHelper::prepareStatement(
			$this->connection, $statement, $tableStructure);

		$this->assertInstanceOf(SQLitePreparedStatement::class,
			$prepared);
		$this->assertEquals(2, $prepared->getParameters()
			->count(), 'Number of parameters in prepared statement');

		$sql = strval($prepared);
		$sql = \SqlFormatter::format(strval($sql), false);
		$this->derivedFileManager->assertDerivedFile($sql, __METHOD__,
			'insert', 'sql');

		$rawPrepared = $this->connection->prepareStatement($sql);

		$this->assertEquals(2, $rawPrepared->getParameters()
			->count(),
			'Number of parameters in prepared statement from raw SQL');

		$p = [];

		$p['nameValue'] = 'Bob';
		$p['salaryValue'] = 2000;
		$result = $this->connection->executeStatement($prepared, $p);
		$this->assertInstanceOf(
			InsertionStatementResultInterface::class, $result);

		$p['nameValue'] = 'Ron';
		$result = $this->connection->executeStatement($prepared, $p);
		$this->assertInstanceOf(
			InsertionStatementResultInterface::class, $result);

		$statement = new SelectQuery($tableStructure);

		$prepared = ConnectionHelper::prepareStatement(
			$this->connection, $statement, $tableStructure);

		$this->assertInstanceOf(SQLitePreparedStatement::class,
			$prepared);

		$this->assertEquals(4, $prepared->getResultColumns()
			->count(), 'Prepared statement result columns count');

		$statement = new SelectQuery($tableStructure);
		$statement->columns('name', 'gender', 'salary');

		$prepared = ConnectionHelper::prepareStatement(
			$this->connection, $statement, $tableStructure);

		$this->assertInstanceOf(SQLitePreparedStatement::class,
			$prepared);
		$this->assertEquals(3, $prepared->getResultColumns()
			->count(), 'Prepared statement result columns count');

		// Testing the nicely crafted query
		// and the ugly hard-coded one
		$statements = [
			$prepared,
			'select name, gender, salary from Employees'
		];

		foreach ($statements as $statement)
		{
			$result = $this->connection->executeStatement($statement);
			$this->assertInstanceOf(SQLiteRecordset::class, $result);

			$this->assertCount(3, $result->getResultColumns(),
				'Recordset result columns count');

			$expected = [
				[
					'name' => 'Bob',
					'gender' => 'M',
					'salary' => 2000.
				],
				[
					'name' => 'Ron',
					'gender' => 'M',
					'salary' => 2000.
				]
			];

			list ($_, $expectedResultColumnKeys) = Container::first(
				$expected);
			$index = 0;
			foreach ($expectedResultColumnKeys as $name => $_)
			{
				$byIndex = $result->getResultColumns()->get($index);
				$this->assertEquals($name, $byIndex->getName(),
					'Recordset result column #' . $index);
				$byName = $result->getResultColumns()->get($name);
				$this->assertEquals($name, $byName->getName(),
					'Recordset result column ' . $name);
				$index++;
			}

			$index = 0;
			foreach ($result as $row)
			{
				foreach ($expected[$index] as $name => $value)
				{
					$this->assertEquals($value, $row[$name],
						'Row ' . $index . ' column ' . $name);
				}

				$index++;
			}
		}

		// Same with parameters

		$statements = [
			'select name, salary from employees where name > :param'
		];

		foreach ($statements as $statement)
		{

			$tests = [
				'Bob only' => [
					'param' => 'Bob',
					'rows' => [
						[
							'name' => 'Bob',
							'gender' => 'M',
							'salary' => 2000
						]
					]
				],
				'Empty' => [
					'param' => 'Zob',
					'rows' => []
				]
			];

			foreach ($tests as $testName => $test)
			{
				$params = [];
				$params['param'] = $test['param'];
				$result = $this->connection->executeStatement(
					$statement, $params);

				$this->assertInstanceOf(SQLiteRecordset::class, $result,
					$testName . ' result object');

				$index = 0;
				foreach ($result as $row)
				{
					$this->assertArrayHasKey($index, $test['rows'],
						'Rows of ' . $testName);
					$index++;
				}

				$this->assertEquals(count($test['rows']), $index,
					'Number of row of ' . $testName);
			}
		}
	}

	public function testRawPragmas()
	{
		if (!$this->prerequisites())
			return;

		$this->assertTrue($this->createDatabase());

		$result = $this->connection->executeStatement(
			'PRAGMA database_list');

		$this->assertInstanceOf(Recordset::class, $result,
			'Pragma returns a recordset');
	}

	public function testTimestampFormat()
	{
		if (!$this->prerequisites())
			return;
		$platform = new SQLitePlatform();

		StatementBuilder::getInstance(); // IDO workaround
		$dateTimeFormat = DateTime::getFormatTokenDescriptions();

		foreach ($dateTimeFormat as $token => $info)
		{
			$translation = $platform->getTimestampFormatTokenTranslation(
				$token);
			$this->assertTrue($translation !== null,
				$token . ' translation rule exists');
		}

		$timestamp = new DateTime('2010-11-12 13:14:15+02:00');

		$tests = [
			'date' => [
				'format' => 'Y-m-d',
				'translation' => '%Y-%m-%d'
			],
			'time' => [
				'format' => 'H:i:s',
				'translation' => '%H:%M:%S'
			],
			'Escaped' => [
				'format' => 'Y-m-d\TH:i:s',
				'translation' => '%Y-%m-%dT%H:%M:%S'
			]
		];

		foreach ($tests as $label => $test)
		{
			$test = (object) $test;
			$tf = new TimestampFormatFunction($test->format, $timestamp);
			$f = $platform->translateFunction($tf);
			$this->assertInstanceOf(FunctionCall::class, $f,
				$label . ' translated function');

			$format = $f->getArgument(0)->getValue();
			$this->assertEquals($test->translation, $format,
				$label . ' translated format');
		}
	}

	public function testStructureExplorer()
	{
		if (!$this->prerequisites())
			return;
		$environment = new Environment(
			[
				K::CONNECTION_TYPE => SQLiteConnection::class,
				K::CONNECTION_SOURCE => [
					'ACME' => __DIR__ . '/../data/Company.sqlite',
					__DIR__ . '/../data/keyvalue.sqlite'
				]
			]);

		$connection = $environment->getConnection();

		/**
		 *
		 * @var StructureExplorerInterface $explorer
		 */
		$explorer = $connection->getStructureExplorer();

		$this->assertInstanceOf(StructureExplorerInterface::class,
			$explorer);

		$namespaces = $explorer->getNamespaceNames();

		$this->assertContains('ACME', $namespaces, 'Namespace names');
		$this->assertContains('keyvalue', $namespaces, 'Namespace names');

		$acmeViews = $explorer->getViewNames('acme');
		$this->assertEquals([
			'Managers'
		], $acmeViews);

		$acmeTables = $explorer->getTableNames('acme');
		$this->assertEquals(
			[
				'Employees',
				'Hierarchy',
				'Tasks',
				'types'
			], $acmeTables, 'ACME table names');

		$keyvalueTables = $explorer->getTableNames([
			'keyvalue'
		]);
		$this->assertEquals([
			'keyvalue'
		], $keyvalueTables, 'keyvalue table names');

		if (false)
		{
			$employeesIndexes = $explorer->getTableIndexNames(
				'acme.Employees');

			$this->assertEquals([
				'index_employees_name'
			], $employeesIndexes, 'Employees table indexes');
		}

		$employeesColumn = $explorer->getTableColumnNames(
			[
				'acme',
				'Employees'
			]);

		$this->assertEquals([
			'id',
			'name',
			'gender',
			'salary'
		], $employeesColumn, 'ACME employees column');

		$tasksPrimaryKey = $explorer->getTablePrimaryKeyConstraint(
			'ACME.Tasks');

		$this->assertInstanceOf(PrimaryKeyTableConstraint::class,
			$tasksPrimaryKey, 'Tasks primary key');

		$tasksForeignKeys = $explorer->getTableForeignKeyConstraints(
			'ACME.Tasks');

		$this->assertCount(2, $tasksForeignKeys,
			'Task foreign key count');

		$structure = $explorer->getStructure();

		$this->assertInstanceOf(DatasourceStructure::class, $structure,
			'Structure from SQLite');

		$acme = $structure['ACME'];
		$this->assertInstanceOf(NamespaceStructure::class, $acme,
			'ACME namespace');

		$employees = $acme['Employees'];
		$this->assertInstanceOf(TableStructure::class, $employees);

		if (false)
		{
			$index = Container::firstValue(
				Container::filter($employees->getConstraints(),
					function ($k, $c) {
						return ($c instanceof KeyTableConstraintInterface &&
						$c->getName() == 'index_employees_name');
					}));

			$this->assertInstanceOf(KeyTableConstraintInterface::class,
				$index);
		}

		/**
		 *
		 * @var TableStructure $tasks
		 */
		$tasks = $acme['Tasks'];
		$this->assertInstanceOf(TableStructure::class, $tasks,
			'Tasks table');

		$this->assertTrue(
			Container::keyExists($tasks->getColumns(), 'id'),
			'Tasks has id column');

		$tasksPrimaryKey = Container::firstValue(
			Container::filter($tasks->getConstraints(),
				function ($k, $constraint) {
					return ($constraint instanceof PrimaryKeyTableConstraint);
				}));

		$this->assertInstanceOf(PrimaryKeyTableConstraint::class,
			$tasksPrimaryKey, 'Tasks primary key');

		$foreignKeys = Container::filter($tasks->getConstraints(),
			function ($k, $v) {
				return ($v instanceof ForeignKeyTableConstraint);
			});

		$this->assertCount(2, $foreignKeys, 'Tasks foreign key count');

		$id = $tasks->getColumns()->get('id');

		$this->assertInstanceOf(ColumnStructure::class, $id);

		$this->assertTrue($id->has(K::COLUMN_FLAGS),
			'Tasks.id has flags');

		$idFlags = $id->get(K::COLUMN_FLAGS);

		$this->assertEquals(K::COLUMN_FLAG_AUTO_INCREMENT,
			$idFlags & K::COLUMN_FLAG_AUTO_INCREMENT,
			'Tasks.id is auto-increment');

		/**
		 *
		 * @var TableStructure $types
		 */
		$types = $acme['types'];
		$this->assertInstanceOf(TableStructure::class, $types);

		$timestamp = $types['timestamp'];
		$this->assertInstanceOf(ColumnStructure::class, $timestamp);

		$dflt = $timestamp->get(K::COLUMN_DEFAULT_VALUE);
		$this->assertInstanceOf(Data::class, $dflt);
		$this->assertInstanceOf(\DateTimeInterface::class,
			$dflt->GetValue());
	}

	private function prerequisites()
	{
		if (!SQLiteConnection::acceptConnection())
		{
			$this->assertFalse(false);
			return false;
		}

		return true;
	}

	private function getRowValue(StatementData $query, $column,
		$parameters = array())
	{
		$result = $this->connection->executeStatement($query,
			$parameters);
		$this->assertInstanceOf(Recordset::class, $result);
		if ($result instanceof Recordset)
		{
			$row = $result->current();
			if (Container::isArray($row))
				return Container::keyValue($row, $column);
		}
		return null;
	}

	private function createTable(TableStructure $tableStructure)
	{
		$path = \strval($tableStructure->getIdentifier());
		if ($this->createdTables->offsetExists($path))
			return true;

		$factory = $this->connection->getPlatform();
		$q = $factory->newStatement(CreateTableQuery::class,
			$tableStructure);
		$q->createFlags(K::CREATE_EXISTS_CONDITION);
		$prepared = ConnectionHelper::prepareStatement(
			$this->connection, $q);

		$this->assertInstanceOf(SQLitePreparedStatement::class,
			$prepared);

		$sql = strval($prepared);
		$sql = \SqlFormatter::format(strval($sql), false);
		$this->derivedFileManager->assertDerivedFile($sql, __METHOD__,
			'create_' . $tableStructure->getName(), 'sql');

		$this->connection->executeStatement($prepared);

		return true;
	}

	private function createDatabase()
	{
		if ($this->connection instanceof DBMS\ConnectionInterface)
			return true;

		$sqliteFile = $this->derivedFileManager->registerDerivedFile(
			'SQLite', __METHOD__, 'db', 'sqlite');

		if (\file_exists($sqliteFile))
			unlink($sqliteFile);

		$this->connection = ConnectionHelper::createConnection(
			[
				SQLiteConstants::CONNECTION_CREATE => true,
				SQLiteConstants::CONNECTION_SOURCE => [
					'ns_unittests' => $sqliteFile
				],
				SQLiteConstants::CONNECTION_TYPE => SQLiteConnection::class
			]);

		$this->assertInstanceOf(SQLiteConnection::class,
			$this->connection, 'Create connection');

		$this->derivedFileManager->setPersistent($sqliteFile, true);

		return true;
	}

	/**
	 *
	 * @var DatasourceManager
	 */
	private $datasources;

	/**
	 *
	 * @var DerivedFileManager
	 */
	private $derivedFileManager;

	/**
	 *
	 * @var \ArrayObject
	 */
	private $createdTables;

	/**
	 *
	 * @var \NoreSOurces\SQL\DBMS\ConnectionInterface
	 */
	private $connection;
}