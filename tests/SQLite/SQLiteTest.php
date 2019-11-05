<?php
namespace NoreSources\SQL;

use NoreSources\SQL\SQLite as SQLite;
use PHPUnit\Framework\TestCase;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\ExpressionEvaluator as X;

final class SQLiteTest extends TestCase
{

	public function __construct()
	{
		parent::__construct();
		$this->connection = null;
		$this->derivedFileManager = new DerivedFileManager();
		$this->datasources = new DatasourceManager();
		$this->createdTables = new \ArrayObject();
	}

	public function testParametersEmployees()
	{
		$structure = $this->datasources->get('Company');
		$tableStructure = $structure['ns_unittests']['Employees'];
		$this->assertInstanceOf(TableStructure::class, $tableStructure);

		$this->assertTrue($this->createDatabase(), 'Create SQLite file');

		$this->assertTrue($this->createTable($tableStructure),
			'Create table ' . $tableStructure->getPath());

		//$this->assertTrue(false, 'Abort');

		$statement = new InsertQuery($tableStructure);
		$statement['gender'] = 'M';
		$statement('name', ':nameValue');
		$statement('salary', ':salaryValue');

		$prepared = ConnectionHelper::prepareStatement($this->connection, $statement,
			$tableStructure);

		$this->assertInstanceOf(PreparedStatement::class, $prepared);
		$this->assertEquals(2, $prepared->getParameterCount(),
			'Number of parameters in prepared statement');

		$sql = strval($prepared);
		$sql = \SqlFormatter::format(strval($sql), false);
		$this->derivedFileManager->assertDerivedFile($sql, __METHOD__, 'insert', 'sql');

		$p = new ParameterArray();

		$p->set('nameValue', 'Bob');
		$p->set('salaryValue', 2000);
		$result = $this->connection->executeStatement($prepared, $p);
		$this->assertInstanceOf(InsertionQueryResult::class, $result);

		$p->set('nameValue', 'Ron');
		$result = $this->connection->executeStatement($prepared, $p);
		$this->assertInstanceOf(InsertionQueryResult::class, $result);

		$statement = new SelectQuery($tableStructure);
		$statement->columns('name', 'gender', 'salary');

		$prepared = ConnectionHelper::prepareStatement($this->connection, $statement,
			$tableStructure);

		$this->assertInstanceOf(SQLite\PreparedStatement::class, $prepared);
		$this->assertEquals(3, $prepared->getResultColumnCount(),
			'Prepared statement result columns count');

		// Testing the nicely crafted query
		// and the ugly hard-coded one
		$statements = [
			$prepared,
			'select name, gender, salary from Employees'
		];

		foreach ($statements as $statement)
		{

			$result = $this->connection->executeStatement($statement);
			$this->assertInstanceOf(Recordset::class, $result);

			$this->assertEquals(3, $result->getResultColumnCount(), 'Recordset result columns count');

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
				$params = new ParameterArray();
				$params->set('param', $test['param']);
				$result = $this->connection->executeStatement($statement, $params);

				$this->assertInstanceOf(Recordset::class, $result, $testName . ' result object');

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

		$q = new CreateTableQuery($tableStructure);
		$prepared = ConnectionHelper::prepareStatement($this->connection, $q);

		$this->assertInstanceOf(PreparedStatement::class, $prepared);

		$sql = strval($prepared);
		$sql = \SqlFormatter::format(strval($sql), false);
		$this->derivedFileManager->assertDerivedFile($sql, __METHOD__, 'create', 'sql');

		$this->connection->executeStatement($prepared);

		return true;
	}

	private function createDatabase()
	{
		if ($this->connection instanceof Connection)
			return true;

		$sqliteFile = $this->derivedFileManager->registerDerivedFile('SQLite', __METHOD__, 'db',
			'sqlite');

		if (\file_exists($sqliteFile))
			unlink($sqliteFile);

		$this->connection = ConnectionHelper::createConnection([
				K::CONNECTION_PARAMETER_CREATE => true,
				K::CONNECTION_PARAMETER_SOURCE => [
						'ns_unittests' => $sqliteFile
				],
				K::CONNECTION_PARAMETER_TYPE => SQLite\Connection::class
		]);

		$this->assertInstanceOf(SQLite\Connection::class, $this->connection, 'Create connection');

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