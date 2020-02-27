<?php
namespace NoreSources\SQL;

// Uses
use NoreSources\SQL\DBMS\ConnectionHelper;
use NoreSources\SQL\DBMS\PDO\PDOConnection;
use NoreSources\SQL\DBMS\PDO\PDOConstants as K;
use NoreSources\SQL\Statement\UpdateQuery;
use NoreSources\Test\DatasourceManager;
use NoreSources\Test\DerivedFileManager;

// Globals
$sqliteConnectionParameters = [
	K::CONNECTION_PARAMETER_SOURCE => [
		'sqlite',
		realpath(__DIR__ . '/../data/Company.sqlite')
	]
];

final class PDOTest extends \PHPUnit\Framework\TestCase
{

	public function __construct($name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->derivedFileManager = new DerivedFileManager(__DIR__ . '/..');
		$this->datasources = new DatasourceManager();
	}

	public function testBuildDSN()
	{
		$tests = [
			'sqlite:path/to/file.sqlite' => [
				'sqlite',
				'path/to/file.sqlite'
			],
			'pgsql:dbname=Foo' => [
				'pgsql',
				'dbname' => 'Foo'
			]
		];

		foreach ($tests as $expected => $array)
		{
			$actual = PDOConnection::buildDSN($array);
			$this->assertEquals($expected, $actual);
		}
	}

	public function testBase()
	{
		$drivers = \PDO::getAvailableDrivers();
		$localMethodName = preg_replace(',.*::test(.*),', '\1', __METHOD__);

		foreach ($drivers as $driver)
		{
			$driverMethod = 'subtest' . $driver . $localMethodName;
			if (\method_exists($this, $driverMethod))
				call_user_func([
					$this,
					$driverMethod
				]);
		}
	}

	private function subtestSQLiteBase()
	{
		global $sqliteConnectionParameters;
		$connection = new PDOConnection();
		$connection->connect($sqliteConnectionParameters);

		$recordset = $connection->executeStatement('select * from employees');
		$this->assertInstanceOf(DBMS\PDO\PDORecordset::class, $recordset);

		$expectedRowCount = 4;

		$a = 0;
		foreach ($recordset as $row)
		{
			$a++;
		}

		$this->assertEquals($expectedRowCount, $a, 'First pass, row count');

		$b = 0;
		foreach ($recordset as $row)
		{
			$b++;
		}

		$this->assertEquals($expectedRowCount, $b, 'Second pass, row count');
	}

	public function testSQLiteCreateInsertUpdateDelete()
	{
		$settings = [
			K::CONNECTION_PARAMETER_SOURCE => [
				'sqlite',
				':memory:'
			]
		];

		$structure = $this->datasources->get('Company');

		$tableStructure = $structure['ns_unittests']['Employees'];
		$this->assertInstanceOf(Structure\TableStructure::class, $tableStructure);

		// Detach table from tableset to avoid invalid tableset name
		$detachedTable = clone $tableStructure;
		$detachedTable->detach();

		$connection = new PDOConnection();
		$connection->connect($settings);

		$create = new Statement\CreateTableQuery($detachedTable);
		$sql = ConnectionHelper::getStatementData($connection, $create);
		$sql = \strval($sql);

		$this->derivedFileManager->assertDerivedFile(\SqlFormatter::format($sql, false), __METHOD__,
			'create', 'sql');
		$connection->executeStatement($sql);

		$insert = new Statement\InsertQuery($detachedTable);
		$insert('name', ':name');
		$insert('gender', ':gender');
		$insert('salary', ':salary');

		$prepared = ConnectionHelper::prepareStatement($connection, $insert, $detachedTable);
		$this->assertInstanceOf(DBMS\PDO\PDOPreparedStatement::class, $prepared);
		$sql = strval($prepared);
		$this->derivedFileManager->assertDerivedFile(\SqlFormatter::format($sql, false), __METHOD__,
			'insert', 'sql');

		$employees = [
			[
				'name' => 'John Doe',
				'gender' => 'M',
				'salary' => 4096
			],
			[
				'name' => 'Angelina Jolie',
				'gender' => 'F',
				'salary' => 32768
			],
			[
				'name' => 'Joan of Arc',
				'gender' => 'F',
				'salary' => 0
			],
			[
				'name' => 'Bob Lennon',
				'gender' => 'M',
				'salary' => 1048576
			]
		];

		foreach ($employees as $index => $employee)
		{
			$connection->executeStatement($prepared, $employee);
		}

		$select = new Statement\SelectQuery($detachedTable);
		$select->where([
			'>' => [
				'salary',
				5000
			]
		]);
		$select->orderBy('id');

		$preparedSelect = ConnectionHelper::prepareStatement($connection, $select, $detachedTable);
		$this->assertInstanceOf(DBMS\PDO\PDOPreparedStatement::class, $preparedSelect);
		$sql = strval($preparedSelect);
		$this->derivedFileManager->assertDerivedFile(\SqlFormatter::format($sql, false), __METHOD__,
			'select', 'sql');

		$result = $connection->executeStatement($preparedSelect);
		$this->assertInstanceOf(DBMS\PDO\PDORecordset::class, $result);

		$expected = [
			$employees[1],
			$employees[3]
		];

		$c = 0;
		foreach ($result as $index => $row)
		{
			foreach ($expected[$index] as $name => $value)
			{
				$this->assertEquals($value, $row[$name], '# ' . $index . ' value of ' . $name);
			}
		}

		$update = new UpdateQuery($detachedTable);
		$update('salary', 'salary + 10000');
		$update->where('salary < 5000');

		$prepared = ConnectionHelper::prepareStatement($connection, $update, $detachedTable);
		$this->assertInstanceOf(DBMS\PDO\PDOPreparedStatement::class, $prepared);
		$sql = strval($prepared);
		$this->derivedFileManager->assertDerivedFile(\SqlFormatter::format($sql, false), __METHOD__,
			'update', 'sql');

		$result = $connection->executeStatement($prepared);
		$this->assertInstanceOf(QueryResult\RowModificationQueryResult::class, $result);

		$result = $connection->executeStatement($preparedSelect);
		$this->assertInstanceOf(DBMS\PDO\PDORecordset::class, $result);

		$c = 0;
		foreach ($result as $index => $row)
		{
			foreach ($employees[$index] as $name => $value)
			{
				if ($name == 'salary' && ($value < 5000))
					$value += 10000;
				$this->assertEquals($value, $row[$name], '# ' . $index . ' value of ' . $name);
			}
		}
	}

	private function subtestPgsqlBase()
	{
		return;
		$pdo = new \PDO('pgsql:dbname=renaud');

		$sql = 'select * from employees';
		$prepared = $pdo->prepare($sql);
		$result = $prepared->execute();
		var_dump($result);
		foreach ($prepared as $row)
		{
			var_dump($row);
		}

		$sql = 'select * from "TableName" where name = :n';
		$prepared = $pdo->prepare($sql);
		$prepared->bindValue(':n', 'now');

		$result = $prepared->execute();
		var_dump($result);
		foreach ($prepared as $row)
		{
			var_dump($row);
		}
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
}