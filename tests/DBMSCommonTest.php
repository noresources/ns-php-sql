<?php
namespace NoreSources\SQL;

use NoreSources\Container;
use NoreSources\TypeDescription;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\Connection;
use NoreSources\SQL\DBMS\ConnectionHelper;
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
		$this->derivedFileManager = new DerivedFileManager(__DIR__ . '/..');
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

	private function recreateTable(Connection $connection, TableStructure $tableStructure)
	{
		$drop = new DropTableQuery($tableStructure);
		$sql = ConnectionHelper::getStatementSQL($connection, $drop, $tableStructure);
		$connection->executeStatement($sql);

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