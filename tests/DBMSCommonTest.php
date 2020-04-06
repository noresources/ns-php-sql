<?php
namespace NoreSources\SQL;

use NoreSources\Container;
use NoreSources\DateTime;
use NoreSources\TypeDescription;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\Connection;
use NoreSources\SQL\DBMS\ConnectionException;
use NoreSources\SQL\DBMS\ConnectionHelper;
use NoreSources\SQL\DBMS\PreparedStatement;
use NoreSources\SQL\Expression\Column;
use NoreSources\SQL\Expression\Literal;
use NoreSources\SQL\Expression\TimestampFormatFunction;
use NoreSources\SQL\QueryResult\InsertionQueryResult;
use NoreSources\SQL\QueryResult\Recordset;
use NoreSources\SQL\Statement\DeleteQuery;
use NoreSources\SQL\Statement\DropTableQuery;
use NoreSources\SQL\Statement\InsertQuery;
use NoreSources\SQL\Statement\SelectQuery;
use NoreSources\SQL\Structure\StructureElement;
use NoreSources\SQL\Structure\TableStructure;
use NoreSources\Test\DatasourceManager;
use NoreSources\Test\DerivedFileManager;
use NoreSources\Test\Generator;
use NoreSources\Test\TestConnection;
use PHPUnit\Framework\TestCase;

final class DBMSCommonTest extends TestCase
{

	public function __construct($name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->derivedFileManager = new DerivedFileManager(__DIR__);
		$this->structures = new DatasourceManager();
		$this->connections = new TestConnection();
	}

	public function testTypes()
	{
		$settings = $this->connections->getAvailableConnectionNames();

		foreach ($settings as $dbmsName)
		{
			$this->dbmsTestTypes($dbmsName);
		}
	}

	public function dbmsTestTypes($dbmsName)
	{
		$connection = $this->connections->get($dbmsName);
		$this->assertInstanceOf(Connection::class, $connection, $dbmsName);
		$this->assertTrue($connection->isConnected(), $dbmsName);

		$structure = $this->structures->get('types');
		$this->assertInstanceOf(StructureElement::class, $structure);
		$tableStructure = $structure['ns_unittests']['types'];
		$this->assertInstanceOf(TableStructure::class, $tableStructure);

		$result = $this->recreateTable($connection, $tableStructure);
		$this->assertTrue($result, TypeDescription::getName($connection));

		$rows = [
			'default values' => [
				'base' => [
					'insert' => 'defaults',
					'expected' => 'defaults'
				],
				'binary' => [
					'expected' => 'abc',
					K::COLUMN_DATA_TYPE => K::DATATYPE_BINARY
				],
				'boolean' => [
					'expected' => true
				],
				'large_int' => [
					'expected' => 123456789012
				],
				'small_int' => [
					'expected' => null
				],
				'float' => [
					'expected' => 1.23
				]
			/**
			 * Unfortunately some DBMS does not really support time zone spec
			 * (ex MySQL)
			 */
			//,
			//'timestamp_tz' => [
			//	'expected' => new \DateTime('2010-11-12T13:14:15+0100')
			//]
			],
			'arbitrary data' => [
				'base' => [
					'insert' => 123,
					'expected' => '123'
				],
				'binary' => [
					'expected' => 'abc',
					K::COLUMN_DATA_TYPE => K::DATATYPE_BINARY
				],
				'boolean' => [
					'insert' => false,
					'expected' => false
				],
				'large_int' => [
					'insert' => 161234567890,
					'expected' => 161234567890
				],
				'small_int' => [
					'insert' => 127,
					'expected' => 127
				],
				'float' => [
					'insert' => 4.56,
					'expected' => 4.56
				]
			/**
			 * Unfortunately some DBMS does not really support time zone spec
			 * (ex MySQL)
			 */
			//'timestamp_tz' => [
			//	'insert' => new \DateTime('2010-11-12T13:14:15+0400'),
			//	'expected' => new \DateTime('2010-11-12T13:14:15+0400')
			//]
			]
		];

		$rowQueries = [];

		foreach ($rows as $label => $columns)
		{
			$q = new InsertQuery($tableStructure);
			foreach ($columns as $columnName => $specs)
			{
				if (Container::keyExists($specs, 'insert'))
				{
					$as = $q->setColumnValue($columnName, $specs['insert'],
						Container::keyValue($specs, 'evaluate', false));
				}
			}

			$data = ConnectionHelper::getStatementData($connection, $q, $tableStructure);
			$rowQueries[$label] = \strval($data);
			$result = $connection->executeStatement($data);

			$this->assertInstanceOf(InsertionQueryResult::class, $result, $label);
		}

		$q = new SelectQuery($tableStructure);
		$q->orderBy('int');
		$data = ConnectionHelper::getStatementData($connection, $q, $tableStructure);

		$recordset = $connection->executeStatement($data);
		$this->assertInstanceOf(Recordset::class, $recordset, $dbmsName);
		$recordset->setFlags($recordset->getFlags() | Recordset::FETCH_UNSERIALIZE);

		if ($recordset instanceof \Countable)
		{
			$this->assertCount(\count($rows), $recordset,
				$dbmsName . ' ' . $label . ' record count (Countable interface)');
		}
		else
		{
			$c = clone $q;
			$c->columns([
				'count (base)' => 'c'
			]);
			$cd = ConnectionHelper::getStatementData($connection, $c, $tableStructure);
			$cr = $connection->executeStatement($cd);
			$this->assertInstanceOf(Recordset::class, $cr, $dbmsName . ' select count ()');

			$cv = $cr->current();
		}

		reset($rows);
		$count = 0;
		foreach ($recordset as $index => $record)
		{
			list ($label, $columns) = each($rows);
			$count++;
			foreach ($columns as $columnName => $specs)
			{
				if (!Container::keyExists($specs, 'expected'))
					continue;

				$expected = $specs['expected'];
				$actual = $record[$columnName];
				$this->assertEquals($expected, $actual,
					$dbmsName . ':' . $index . ':' . $label . ':' . $columnName . ' value' . PHP_EOL .
					$rowQueries[$label]);
			}
		}

		$this->assertEquals(\count($rows), $count, 'Recordset count (iterate)');

		// Binary data insertion
		{
			$i = new InsertQuery($tableStructure);
			$fileName = 'binary-content.data';
			$content = file_get_contents(__DIR__ . '/data/' . $fileName);
			$i['base'] = $fileName;
			$i['binary'] = $content;

			$result = $connection->executeStatement(
				ConnectionHelper::getStatementData($connection, $i, $tableStructure));

			$this->assertInstanceOf(InsertionQueryResult::class, $result,
				$fileName . ' binary insert');

			$s = new SelectQuery($tableStructure);
			$s->orderBy('int');
			$s->columns('binary');
			$s->where([
				'base' => new Literal($fileName)
			]);

			$result = $connection->executeStatement(
				ConnectionHelper::getStatementData($connection, $s, $tableStructure));

			$this->assertInstanceOf(Recordset::class, $result,
				$dbmsName . ' ' . $fileName . ' select');

			$result->setFlags($result->getFlags() | Recordset::FETCH_UNSERIALIZE);
			if ($result instanceof \Countable)
				$this->assertCount(1, $result, $dbmsName . ' ' . $fileName . ' count');

			$row = $result->current();

			$this->assertEquals($content, $row['binary'],
				$dbmsName . ' ' . $fileName . ' content from db');
		}

		// Timestamp formatting
		{
			$timestamps = [];
			for ($i = 0; $i < 10; $i++)
			{
				$timestamps[] = Generator::randomDateTime(
					[
						'yearRange' => [
							// PostgreSQL wants a special format of BC dates
							//
							1,
							2123
						],

						'timezone' => DateTime::getUTCTimezone()
					]);
			}

			$formats = [
				'date' => 'Y-m-d',
				'time' => 'H:i:s'
			];

			$i = new InsertQuery($tableStructure);
			$i('base', ':id');
			$i('timestamp', ':timestamp');

			$prepared = ConnectionHelper::prepareStatement($connection, $i, $tableStructure);

			$c = \count($timestamps);
			for ($i = 0; $i < $c; $i++)
			{
				$id = 'timestamp_format_' . $i;
				$result = $connection->executeStatement($prepared,
					[
						'id' => $id,
						'timestamp' => $timestamps[$i]
					]);
			}

			foreach ($formats as $label => $format)
			{
				$s = new SelectQuery($tableStructure);
				$s->orderBy('int');
				$s->columns('timestamp',
					[
						new TimestampFormatFunction($format, new Column('timestamp')),
						'format'
					]);
				$s->where("base like 'timestamp_format_%'")->orderBy('base');

				$statement = ConnectionHelper::getStatementData($connection, $s, $tableStructure);

				$result = $connection->executeStatement($statement);
				$this->assertInstanceOf(Recordset::class, $result);

				if ($result instanceof \Countable)
					$this->assertCount(\count($timestamps), $result);

				$i = 0;
				//$result->setFlags($result->getFlags() | Recordset::FETCH_UNSERIALIZE);
				foreach ($result as $row)
				{
					/*
					 * Most of DBMS outputs UTC based values
					 */
					$dt = clone $timestamps[$i];
					$dt->setTimezone(DateTime::getUTCTimezone());

					$expected = $dt->format($format);
					$actual = $row['format'];
					$this->assertEquals($expected, $actual,
						$dbmsName . ' timestamp format ' . $format . ' of #' . $i . ' ' .
						$timestamps[0]->format(\DateTIme::ISO8601) . PHP_EOL . \strval($statement));
					$i++;
				}
			}
		}
	}

	public function testParametersTypes()
	{
		$settings = $this->connections->getAvailableConnectionNames();

		foreach ($settings as $dbmsName)
		{
			$connection = $this->connections->get($dbmsName);
			$this->assertInstanceOf(Connection::class, $connection, $dbmsName);
			$this->assertTrue($connection->isConnected(), $dbmsName);

			$structure = $this->structures->get('types');
			$this->assertInstanceOf(StructureElement::class, $structure);
			$tableStructure = $structure['ns_unittests']['types'];
			$this->assertInstanceOf(TableStructure::class, $tableStructure);

			$this->recreateTable($connection, $tableStructure);
			$this->dbmsParametersTypes($connection, $tableStructure);
		}
	}

	private function dbmsParametersTypes(Connection $connection, TableStructure $tableStructure)
	{
		$dbmsName = TypeDescription::getLocalName($connection);
		$method = __CLASS__ . '::' . debug_backtrace()[1]['function'];
		$i = new InsertQuery($tableStructure);
		$i('int', ':even');
		$i('large_int', ':odd');
		$i('small_int', ':even');

		$select = ConnectionHelper::getStatementData($connection, new SelectQuery($tableStructure),
			$tableStructure);

		$delete = ConnectionHelper::getStatementData($connection, new DeleteQuery($tableStructure),
			$tableStructure);

		$insert = ConnectionHelper::prepareStatement($connection, $i, $tableStructure);
		$sql = \SqlFormatter::format(\strval($insert), false);
		$this->derivedFileManager->assertDerivedFile($sql, $method, $dbmsName . '_insert', 'sql');

		$tests = [
			[
				'parameters' => [
					'even' => 2,
					'odd' => 1
				],
				'expected' => [
					'int' => 2,
					'large_int' => 1,
					'small_int' => 2
				]
			]
		];

		foreach ($tests as $label => $test)
		{
			$this->connections->queryTest($connection, $insert, $test['parameters'],
				$test['expected'], $select, $delete);
		}

		$insert = new InsertQuery($tableStructure);
		$insert('binary', ':bin');
		$insert = ConnectionHelper::prepareStatement($connection, $insert, $tableStructure);

		$tests = [
			'int' => [
				'parameters' => [
					'bin' => 0xdeadbeef // 3735928559
				],
				'expected' => [
					'binary' => 0xdeadbeef
				]
			],
			'float' => [
				'parameters' => [
					'bin' => 1.2345
				],
				'expected' => [
					'binary' => 1.2345
				]
			],
			'text' => [
				'parameters' => [
					'bin' => 'Hello world'
				],
				'expected' => [
					'binary' => 'Hello world'
				]
			],
			'null' => [
				'parameters' => [
					'bin' => null
				],
				'expected' => [
					'binary' => null
				]
			]
		];

		foreach ($tests as $label => $test)
		{
			$this->connections->queryTest($connection, $insert, $test['parameters'],
				$test['expected'], $select, $delete);
		}

		$insert = new InsertQuery($tableStructure);
		$insert('binary', ':bin');
		$insert('base', ':base');
		$insert('large_int', ':int');
		$insert = ConnectionHelper::prepareStatement($connection, $insert, $tableStructure);

		$tests = [
			'missing bin' => [
				'parameters' => [
					'int' => 456,
					'base' => 'Missing :bin parameter'
				],
				'expected' => [
					'binary' => null,
					'base' => 'Missing :bin parameter',
					'large_int' => 456
				]
			],
			'missing int' => [
				'parameters' => [
					'bin' => 0xcafe,
					'base' => 'Missing :int parameter'
				],
				'expected' => [
					'binary' => 0xcafe,
					'base' => 'Missing :int parameter',
					'large_int' => null
				]
			],
			'missing int and bin' => [
				'parameters' => [
					'base' => 'Missing :int parameter'
				],
				'expected' => [
					'binary' => null,
					'base' => 'Missing :int parameter',
					'large_int' => null
				]
			]
		];

		foreach ($tests as $label => $test)
		{
			$this->connections->queryTest($connection, $insert, $test['parameters'],
				$test['expected'], $select, $delete);
		}
	}

	public function testParametersEmployees()
	{
		$structure = $this->structures->get('Company');
		$tableStructure = $structure['ns_unittests']['Employees'];
		$this->assertInstanceOf(Structure\TableStructure::class, $tableStructure);

		$settings = $this->connections->getAvailableConnectionNames();

		foreach ($settings as $dbmsName)
		{
			$connection = $this->connections->get($dbmsName);
			$this->assertInstanceOf(Connection::class, $connection, $dbmsName);
			$this->assertTrue($connection->isConnected(), $dbmsName);

			$this->employeesTest($tableStructure, $connection);
		}
	}

	private function employeesTest(TableStructure $tableStructure, Connection $connection)
	{
		$dbmsName = TypeDescription::getLocalName($connection);
		$method = __CLASS__ . '::' . debug_backtrace()[1]['function'];
		$this->recreateTable($connection, $tableStructure);

		// Insert QUery
		$insertQuery = new InsertQuery($tableStructure);
		$insertQuery->setColumnValue('id', ':identifier', true);
		$insertQuery['gender'] = 'M';
		$insertQuery('name', ':nameValue');
		$insertQuery('salary', ':salaryValue');

		$preparedInsert = ConnectionHelper::prepareStatement($connection, $insertQuery,
			$tableStructure);

		$this->assertInstanceOf(PreparedStatement::class, $preparedInsert, $dbmsName);

		$this->assertEquals(3, $preparedInsert->getParameters()
			->count(), 'Number of parameters in prepared statement');

		$sql = strval($preparedInsert);
		$sql = \SqlFormatter::format(strval($sql), false);
		$this->derivedFileManager->assertDerivedFile($sql, __METHOD__, $dbmsName . '_insert', 'sql');

		$p = [
			'nameValue' => 'Bob',
			'salaryValue' => 2000,
			'identifier' => 1
		];

		$result = $connection->executeStatement($preparedInsert, $p);
		$this->assertInstanceOf(QueryResult\InsertionQueryResult::class, $result,
			$dbmsName . ' ' . $preparedInsert);

		$p['identifier'] = 2;
		$p['nameValue'] = 'Ron';
		$result = $connection->executeStatement($preparedInsert, $p);
		$this->assertInstanceOf(QueryResult\InsertionQueryResult::class, $result,
			$dbmsName . ' ' . $preparedInsert);

		// Test result column count when no column are specified (select * from ...)
		$basicSelectQuery = new SelectQuery($tableStructure);
		$preparedBasicSelect = ConnectionHelper::prepareStatement($connection, $basicSelectQuery,
			$tableStructure);
		$this->assertInstanceOf(PreparedStatement::class, $preparedBasicSelect, $dbmsName);

		$this->assertCount(4, $preparedBasicSelect->getResultColumns(),
			$dbmsName . ' Prepared statement result columns count (auto-detected)');

		$selectColumnQuery = new SelectQuery($tableStructure);
		$selectColumnQuery->columns('name', 'gender', 'salary');

		$preparedSelectColumn = ConnectionHelper::prepareStatement($connection, $selectColumnQuery,
			$tableStructure);
		$this->assertInstanceOf(PreparedStatement::class, $preparedSelectColumn, $dbmsName);
		$this->assertCount(3, $preparedSelectColumn->getResultColumns(),
			$dbmsName . ' Prepared statement result columns count');

		$result = $connection->executeStatement($preparedSelectColumn);
		$this->assertInstanceOf(Recordset::class, $result, $dbmsName, $preparedSelectColumn);

		$this->assertCount(3, $result->getResultColumns(),
			$dbmsName . ' Recordset result columns count');

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
			$byIndex = $result->getResultColumns()->getColumn($index);
			$this->assertEquals($name, $byIndex->name,
				$dbmsName . ' Recordset result column #' . $index);
			$byName = $result->getResultColumns()->getColumn($name);
			$this->assertEquals($name, $byName->name,
				$dbmsName . ' Recordset result column ' . $name);
			$index++;
		}

		$index = 0;
		foreach ($result as $row)
		{
			foreach ($expected[$index] as $name => $value)
			{
				$this->assertEquals($value, $row[$name],
					$dbmsName . ' Row ' . $index . ' column ' . $name);
			}

			$index++;
		}

		$selectByNameParamQuery = new SelectQuery('Employees');
		$selectByNameParamQuery->columns('name', 'salary');
		$selectByNameParamQuery->where([
			'=' => [
				'name',
				':param'
			]
		]);

		// Not a prepared statement but containts enough informations
		$backedSelectByName = ConnectionHelper::getStatementData($connection,
			$selectByNameParamQuery, $tableStructure);

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
			$result = $connection->executeStatement($backedSelectByName, $params);

			$this->assertInstanceOf(Recordset::class, $result,
				$dbmsName . ' ' . $testName . ' result object');

			for ($pass = 1; $pass <= 2; $pass++)
			{
				$index = 0;
				foreach ($result as $row)
				{
					$this->assertArrayHasKey($index, $test['rows'],
						'Rows of ' . $testName . '(pass ' . $pass . ')');
					$index++;
				}

				$this->assertEquals(count($test['rows']), $index,
					$dbmsName . ' Number of row of ' . $testName . '(pass ' . $pass . ')');
			}
		}
	}

	private function recreateTable(Connection $connection, TableStructure $tableStructure)
	{
		$dbmsName = TypeDescription::getLocalName($connection);
		try // PostgreSQL < 8.2 does not support DROP IF EXISTS and may fail
		{
			$drop = new DropTableQuery($tableStructure);
			$data = ConnectionHelper::getStatementData($connection, $drop, $tableStructure);
			$connection->executeStatement($data);
		}
		catch (ConnectionException $e)
		{}

		$factory = $connection->getStatementFactory();
		$createTable = $factory->newStatement(K::QUERY_CREATE_TABLE, $tableStructure);
		$result = false;
		$data = ConnectionHelper::getStatementData($connection, $createTable, $tableStructure);
		$sql = \SqlFormatter::format(strval($data), false);
		$this->derivedFileManager->assertDerivedFile($sql, __METHOD__,
			$dbmsName . '_create_' . $tableStructure->getName(), 'sql');

		try
		{
			$result = $connection->executeStatement($data);
		}
		catch (\Exception $e)
		{
			$this->assertEquals(true, $result,
				'Create table ' . $tableStructure->getName() . ' on ' .
				TypeDescription::getName($connection) . PHP_EOL . \strval($data) . ': ' .
				$e->getMessage());
		}

		$this->assertTrue($result,
			'Create table ' . $tableStructure->getName() . ' on ' .
			TypeDescription::getName($connection));

		return $result;
	}

	/**
	 *
	 * @var DatasourceManager
	 */
	private $structures;

	/**
	 *
	 * @var TestConnection
	 */
	private $connections;

	/**
	 *
	 * @var DerivedFileManager
	 */
	private $derivedFileManager;
}