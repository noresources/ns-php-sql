<?php
namespace NoreSources\SQL;

use PHPUnit\Framework\TestCase;
use NoreSources\SQL\PDO\Constants as K;
$sqliteConnectionParameters = [
	K::CONNECTION_PARAMETER_SOURCE => [
		'sqlite',
		realpath(__DIR__ . '/../data/Company.sqlite')
	]
];

final class PDOTest extends TestCase
{

	public function __construct()
	{
		parent::__construct();
		$this->connection = null;
		$this->derivedFileManager = new DerivedFileManager();
		$this->datasources = new DatasourceManager();
		$this->createdTables = new \ArrayObject();
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
			$actual = PDO\Connection::buildDSN($array);
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
		$connection = new PDO\Connection();
		$connection->connect($sqliteConnectionParameters);

		$recordset = $connection->executeStatement('select * from employees');
		$this->assertInstanceOf(PDO\Recordset::class, $recordset);

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