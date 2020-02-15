<?php
namespace NoreSources\SQL;

use NoreSources\TypeDescription;
use NoreSources\SQL\DBMS\ConnectionHelper;
use NoreSources\SQL\QueryResult\Recordset;
use NoreSources\SQL\Structure\TableStructure;
use NoreSources\Test\DatasourceManager;
use NoreSources\Test\DerivedFileManager;

final class SQLiteTest extends \PHPUnit\Framework\TestCase
{

	public function __construct($name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->connection = null;
		$this->derivedFileManager = new DerivedFileManager(__DIR__ . '/..');
		$this->datasources = new DatasourceManager();
		$this->createdTables = new \ArrayObject();
	}

	public function testUnserialize()
	{
		$structure = $this->datasources->get('types');
		$tableStructure = $structure['ns_unittests']['types'];
		$this->assertInstanceOf(Structure\TableStructure::class, $tableStructure);

		$this->assertTrue($this->createDatabase(), 'Create SQLite file');

		$this->assertTrue($this->createTable($tableStructure),
			'Create table ' . $tableStructure->getPath());

		// Default values
		$statement = new Statement\InsertQuery($tableStructure);

		$prepared = ConnectionHelper::prepareStatement($this->connection, $statement,
			$tableStructure);

		$sql = \SqlFormatter::format(strval(strval($prepared)), false);
		$this->derivedFileManager->assertDerivedFile($sql, __METHOD__, 'insert', 'sql');

		$result = $this->connection->executeStatement($prepared);
		$this->assertInstanceOf(QueryResult\InsertionQueryResult::class, $result);

		$selectStatement = new Statement\SelectQuery($tableStructure);
		$selectPrepared = ConnectionHelper::prepareStatement($this->connection, $selectStatement,
			$tableStructure);

		$result = $this->connection->executeStatement($selectPrepared);
		$this->assertInstanceOf(DBMS\SQLite\SQLiteRecordset::class, $result);

		$result->setFlags(Recordset::FETCH_ASSOCIATIVE | Recordset::FETCH_UNSERIALIZE);
		$records = $result->getArrayCopy();

		$this->assertCount(1, $records);

		$record = $records[0];

		$this->assertEquals('integer', TypeDescription::getName($record['int']));
		$this->assertEquals('double', TypeDescription::getName($record['float']));
		$this->assertInstanceOf(\DateTime::class, $record['timestamp']);
		$this->assertEquals('boolean', TypeDescription::getName($record['boolean']));
	}

	public function testParametersEmployees()
	{
		$structure = $this->datasources->get('Company');
		$tableStructure = $structure['ns_unittests']['Employees'];
		$this->assertInstanceOf(Structure\TableStructure::class, $tableStructure);

		$this->assertTrue($this->createDatabase(), 'Create SQLite file');

		$this->assertTrue($this->createTable($tableStructure),
			'Create table ' . $tableStructure->getPath());

		$statement = new Statement\InsertQuery($tableStructure);
		$statement['gender'] = 'M';
		$statement('name', ':nameValue');
		$statement('salary', ':salaryValue');

		$prepared = ConnectionHelper::prepareStatement($this->connection, $statement,
			$tableStructure);

		$this->assertInstanceOf(DBMS\SQLite\SQLitePreparedStatement::class, $prepared);
		$this->assertEquals(2, $prepared->getParameters()
			->count(), 'Number of parameters in prepared statement');

		$sql = strval($prepared);
		$sql = \SqlFormatter::format(strval($sql), false);
		$this->derivedFileManager->assertDerivedFile($sql, __METHOD__, 'insert', 'sql');

		$p = [];

		$p['nameValue'] = 'Bob';
		$p['salaryValue'] = 2000;
		$result = $this->connection->executeStatement($prepared, $p);
		$this->assertInstanceOf(QueryResult\InsertionQueryResult::class, $result);

		$p['nameValue'] = 'Ron';
		$result = $this->connection->executeStatement($prepared, $p);
		$this->assertInstanceOf(QueryResult\InsertionQueryResult::class, $result);

		$statement = new Statement\SelectQuery($tableStructure);

		$prepared = ConnectionHelper::prepareStatement($this->connection, $statement,
			$tableStructure);

		$this->assertInstanceOf(DBMS\SQLite\SQLitePreparedStatement::class, $prepared);

		$this->assertEquals(4, $prepared->getResultColumns()
			->count(), 'Prepared statement result columns count');

		$statement = new Statement\SelectQuery($tableStructure);
		$statement->columns('name', 'gender', 'salary');

		$prepared = ConnectionHelper::prepareStatement($this->connection, $statement,
			$tableStructure);

		$this->assertInstanceOf(DBMS\SQLite\SQLitePreparedStatement::class, $prepared);
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
			$this->assertInstanceOf(DBMS\SQLite\SQLiteRecordset::class, $result);

			$this->assertCount(3, $result->getResultColumns(), 'Recordset result columns count');

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

			list ($_, $expectedResultColumnKeys) = each($expected);
			$index = 0;
			foreach ($expectedResultColumnKeys as $name => $_)
			{
				$byIndex = $result->getResultColumn($index);
				$this->assertEquals($name, $byIndex->name, 'Recordset result column #' . $index);
				$byName = $result->getResultColumn($name);
				$this->assertEquals($name, $byName->name, 'Recordset result column ' . $name);
				$index++;
			}

			$index = 0;
			foreach ($result as $row)
			{
				foreach ($expected[$index] as $name => $value)
				{
					$this->assertEquals($value, $row[$name], 'Row ' . $index . ' column ' . $name);
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
				$result = $this->connection->executeStatement($statement, $params);

				$this->assertInstanceOf(DBMS\SQLite\SQLiteRecordset::class, $result,
					$testName . ' result object');

				$index = 0;
				foreach ($result as $row)
				{
					$this->assertArrayHasKey($index, $test['rows'], 'Rows of ' . $testName);
					$index++;
				}

				$this->assertEquals(count($test['rows']), $index, 'Number of row of ' . $testName);
			}
		}
	}

	private function createTable(TableStructure $tableStructure)
	{
		$path = $tableStructure->getPath();
		if ($this->createdTables->offsetExists($path))
			return true;

		$q = new Statement\CreateTableQuery($tableStructure);
		$prepared = ConnectionHelper::prepareStatement($this->connection, $q);

		$this->assertInstanceOf(DBMS\SQLite\SQLitePreparedStatement::class, $prepared);

		$sql = strval($prepared);
		$sql = \SqlFormatter::format(strval($sql), false);
		$this->derivedFileManager->assertDerivedFile($sql, __METHOD__,
			'create_' . $tableStructure->getName(), 'sql');

		$this->connection->executeStatement($prepared);

		return true;
	}

	private function createDatabase()
	{
		if ($this->connection instanceof DBMS\Connection)
			return true;

		$sqliteFile = $this->derivedFileManager->registerDerivedFile('SQLite', __METHOD__, 'db',
			'sqlite');

		if (\file_exists($sqliteFile))
			unlink($sqliteFile);

		$this->connection = ConnectionHelper::createConnection(
			[
				DBMS\SQLite\SQLiteConstants::CONNECTION_PARAMETER_CREATE => true,
				DBMS\SQLite\SQLiteConstants::CONNECTION_PARAMETER_SOURCE => [
					'ns_unittests' => $sqliteFile
				],
				DBMS\SQLite\SQLiteConstants::CONNECTION_PARAMETER_TYPE => DBMS\SQLite\SQLiteConnection::class
			]);

		$this->assertInstanceOf(DBMS\SQLite\SQLiteConnection::class, $this->connection,
			'Create connection');

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
	 * @var Connection
	 */
	private $connection;
}