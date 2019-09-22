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

		$this->assertTrue($this->createTable($tableStructure), 'Create table ' .
			$tableStructure->getPath());

		//$this->assertTrue(false, 'Abort');

		$statement = new InsertQuery($tableStructure);
		$statement['gender'] = 'M';
		$statement('name', ':nameValue');
		$statement('salary', ':salaryValue');

		$statement = ConnectionHelper::prepareStatement($this->connection, $statement, $tableStructure);
		
		$this->assertInstanceOf(PreparedStatement::class, $statement);

		$sql = strval($statement);
		$sql = \SqlFormatter::format(strval($sql), false);
		$this->derivedFileManager->assertDerivedFile($sql, __METHOD__, 'insert', 'sql');

		$p = new ParameterArray();

		$p->set('nameValue', 'Bob');
		$p->set('salaryValue', 2000);
		$result = $this->connection->executeStatement($statement, $p);
		$this->assertInstanceOf(Recordset::class, $result);

		$p->set('nameValue', 'Ron');
		$result = $this->connection->executeStatement($statement, $p);
		$this->assertInstanceOf(Recordset::class, $result);

		$statement = new SelectQuery($tableStructure);
		$statement->columns('name', 'gender', 'salary');
		$statement = ConnectionHelper::prepareStatement($this->connection, $statement, $tableStructure);
		$result = $this->connection->executeStatement($statement);
		$this->assertInstanceOf(Recordset::class, $result);
		
		$expected = [
				['name' => 'Bob', 'gender' => 'M', 'salary' => 2000.],
				['name' => 'Ron', 'gender' => 'M', 'salary' => 2000.]
		];

		$index = 0;
		foreach ($result as $row)
		{
			foreach ($expected[$index] as $name => $value)
			{
				$this->assertEquals($value, $row[$name], 'Row ' . $index . ' column ' .
					$name);
			}

			$index++;
		}
	}

	private function createTable(TableStructure $tableStructure)
	{
		$path = $tableStructure->getPath();
		if ($this->createdTables->offsetExists($path))
			return true;

		$q = new CreateTableQuery($tableStructure);
		$statement = ConnectionHelper::prepareStatement($this->connection, $q);
		
		$this->assertInstanceOf(PreparedStatement::class, $statement);

		$this->connection->executeStatement($statement);

		return true;
	}

	private function createDatabase()
	{
		if ($this->connection instanceof Connection)
			return true;

		$sqliteFile = $this->derivedFileManager->registerDerivedFile('SQLite', __METHOD__, 'db', 'sqlite');

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
	 * @var DatasourceManager
	 */
	private $datasources;

	/**
	 * @var DerivedFileManager
	 */
	private $derivedFileManager;

	/**
	 * @var \ArrayObject
	 */
	private $createdTables;

	/**
	 * @var Connection
	 */
	private $connection;
}