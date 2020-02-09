<?php
namespace NoreSources\SQL;

use NoreSources\Container;
use NoreSources\TypeDescription;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\Connection;
use NoreSources\SQL\DBMS\ConnectionException;
use NoreSources\SQL\DBMS\ConnectionHelper;
use NoreSources\SQL\DBMS\PreparedStatement;
use NoreSources\SQL\QueryResult\InsertionQueryResult;
use NoreSources\SQL\QueryResult\Recordset;
use NoreSources\SQL\Statement\CreateTableQuery;
use NoreSources\SQL\Statement\DropTableQuery;
use NoreSources\SQL\Statement\InsertQuery;
use NoreSources\SQL\Statement\SelectQuery;
use NoreSources\SQL\Structure\StructureElement;
use NoreSources\SQL\Structure\TableStructure;
use NoreSources\Test\DatasourceManager;
use NoreSources\Test\DerivedFileManager;
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
			$connection = $this->connections->get($dbmsName);
			$this->assertInstanceOf(Connection::class, $connection, $dbmsName);
			$this->assertTrue($connection->isConnected(), $dbmsName);

			$structure = $this->structures->get('types');
			$this->assertInstanceOf(StructureElement::class, $structure);
			$tableStructure = $structure['ns_unittests']['types'];
			$this->assertInstanceOf(TableStructure::class, $tableStructure);

			$this->recreateTable($connection, $tableStructure);

			$rows = [
				'default values' => [
					'base' => [
						'insert' => 'defaults',
						'expected' => 'defaults'
					],
					'binary' => [
						'expected' => 'abc',
						K::COLUMN_PROPERTY_DATA_TYPE => K::DATATYPE_BINARY
					],
					'boolean' => [
						'expected' => true
					],
					'int' => [
						'expected' => 3
					],
					'large_int' => [
						'insert' => 16123456789,
						'expected' => 16123456789
					],
					'small_int' => [
						'expected' => null
					],
					'float' => [
						'expected' => 1.23
					],
					'timestamp_tz' => [
						'expected' => new \DateTime('2010-11-12T13:14:15+0100')
					]
				]
			];

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

				$sql = ConnectionHelper::getStatementSQL($connection, $q, $tableStructure);
				$result = $connection->executeStatement($sql);

				$this->assertInstanceOf(InsertionQueryResult::class, $result, $label);
			}

			$q = new SelectQuery($tableStructure);
			$sql = ConnectionHelper::getStatementData($connection, $q, $tableStructure);
			$recordset = $connection->executeStatement($sql);
			$this->assertInstanceOf(Recordset::class, $recordset, $dbmsName);
			$recordset->setFlags($recordset->getFlags() | Recordset::FETCH_UNSERIALIZE);

			if ($recordset instanceof \Countable)
				$this->assertCount(\count($rows), $recordset,
					$dbmsName . ' ' . $label . ' record count');

			reset($rows);
			$count = 0;
			foreach ($recordset as $record)
			{
				list ($label, $columns) = each($rows);
				$count++;
				foreach ($columns as $columnName => $specs)
				{
					if (!Container::keyExists($specs, 'expected'))
						continue;

					$expected = $specs['expected'];
					$this->assertEquals($record[$columnName], $expected,
						$dbmsName . ':' . $label . ':' . $columnName . ' value');
				}
			}

			$this->assertEquals(\count($rows), $count, 'Recordset count (iterate)');
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

		$this->assertEquals(3, $preparedInsert->getParameterCount(),
			'Number of parameters in prepared statement');

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

		$this->assertEquals(4, $preparedBasicSelect->getResultColumnCount(),
			$dbmsName . ' Prepared statement result columns count (auto-detected)');

		$selectColumnQuery = new SelectQuery($tableStructure);
		$selectColumnQuery->columns('name', 'gender', 'salary');

		$preparedSelectColumn = ConnectionHelper::prepareStatement($connection, $selectColumnQuery,
			$tableStructure);
		$this->assertInstanceOf(PreparedStatement::class, $preparedSelectColumn, $dbmsName);
		$this->assertEquals(3, $preparedSelectColumn->getResultColumnCount(),
			$dbmsName . ' Prepared statement result columns count');

		$result = $connection->executeStatement($preparedSelectColumn);
		$this->assertInstanceOf(Recordset::class, $result, $dbmsName, $preparedSelectColumn);

		$this->assertEquals(3, $result->getResultColumnCount(),
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
			$byIndex = $result->getResultColumn($index);
			$this->assertEquals($name, $byIndex->name,
				$dbmsName . ' Recordset result column #' . $index);
			$byName = $result->getResultColumn($name);
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

			$index = 0;
			foreach ($result as $row)
			{
				$this->assertArrayHasKey($index, $test['rows'], 'Rows of ' . $testName);
				$index++;
			}

			$this->assertEquals(count($test['rows']), $index,
				$dbmsName . ' Number of row of ' . $testName);
		}
	}

	private function recreateTable(Connection $connection, TableStructure $tableStructure)
	{
		try
		{
			$drop = new DropTableQuery($tableStructure);
			$sql = ConnectionHelper::getStatementSQL($connection, $drop, $tableStructure);
			$connection->executeStatement($sql);
		}
		catch (ConnectionException $e)
		{}

		$createTable = new CreateTableQuery($tableStructure);
		$sql = ConnectionHelper::getStatementSQL($connection, $createTable, $tableStructure);
		$result = $connection->executeStatement($sql);
		$this->assertEquals(true, $result,
			'Create table ' . $tableStructure->getName() . ' on ' .
			TypeDescription::getName($connection));
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