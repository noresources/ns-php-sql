<?php
namespace NoreSources\SQL;

use NoreSources\Container;
use NoreSources\DateTime;
use NoreSources\TypeDescription;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\ConnectionHelper;
use NoreSources\SQL\DBMS\SQLite\SQLiteConnection;
use NoreSources\SQL\DBMS\SQLite\SQLiteConstants;
use NoreSources\SQL\DBMS\SQLite\SQLitePlatform;
use NoreSources\SQL\DBMS\SQLite\SQLitePreparedStatement;
use NoreSources\SQL\DBMS\SQLite\SQLiteRecordset;
use NoreSources\SQL\DBMS\SQLite\SQLiteStatementBuilder;
use NoreSources\SQL\Expression\FunctionCall;
use NoreSources\SQL\Expression\TimestampFormatFunction;
use NoreSources\SQL\Result\InsertionStatementResultInterface;
use NoreSources\SQL\Result\Recordset;
use NoreSources\SQL\Statement\StatementData;
use NoreSources\SQL\Statement\Manipulation\InsertQuery;
use NoreSources\SQL\Statement\Query\SelectQuery;
use NoreSources\SQL\Structure\TableStructure;
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

	public function testUnserialize()
	{
		$structure = $this->datasources->get('types');
		$tableStructure = $structure['ns_unittests']['types'];
		$this->assertInstanceOf(Structure\TableStructure::class,
			$tableStructure);

		$this->assertTrue($this->createDatabase(), 'Create SQLite file');

		$this->assertTrue($this->createTable($tableStructure),
			'Create table ' . $tableStructure->getPath());

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
		$structure = $this->datasources->get('Company');
		$tableStructure = $structure['ns_unittests']['Employees'];
		$this->assertInstanceOf(Structure\TableStructure::class,
			$tableStructure);

		$this->assertTrue($this->createDatabase(), 'Create SQLite file');

		$this->assertTrue($this->createTable($tableStructure),
			'Create table ' . $tableStructure->getPath());

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

			list ($_, $expectedResultColumnKeys) = each($expected);
			$index = 0;
			foreach ($expectedResultColumnKeys as $name => $_)
			{
				$byIndex = $result->getResultColumns()->getColumn(
					$index);
				$this->assertEquals($name, $byIndex->name,
					'Recordset result column #' . $index);
				$byName = $result->getResultColumns()->getColumn($name);
				$this->assertEquals($name, $byName->name,
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

	public function testTimestampFormat()
	{
		$builder = new SQLiteStatementBuilder(new SQLitePlatform());

		$dateTimeFormat = DateTime::getFormatTokenDescriptions();

		foreach ($dateTimeFormat as $token => $info)
		{
			$translation = $builder->getPlatform()->getTimestampFormatTokenTranslation(
				$token);
			$this->assertTrue($translation !== null,
				$token . ' translation rule exists');
		}

		$timestamp = '2010-11-12 13:14:15+02:00';

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
			$f = $builder->getPlatform()->translateFunction($tf);
			$this->assertInstanceOf(FunctionCall::class, $f,
				$label . ' translated function');

			$format = $f->getArgument(0)->getValue();
			$this->assertEquals($test->translation, $format,
				$label . ' translated format');
		}
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
		$path = $tableStructure->getPath();
		if ($this->createdTables->offsetExists($path))
			return true;

		$factory = $this->connection->getStatementBuilder();
		$q = $factory->newStatement(K::QUERY_CREATE_TABLE,
			$tableStructure);
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