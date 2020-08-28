<?php
namespace NoreSources\SQL;

use NoreSources\Container;
use NoreSources\DateTime;
use NoreSources\SingletonTrait;
use NoreSources\TypeDescription;
use NoreSources\Logger\ErrorReporterLogger;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\ConnectionException;
use NoreSources\SQL\DBMS\ConnectionHelper;
use NoreSources\SQL\DBMS\ConnectionInterface;
use NoreSources\SQL\DBMS\PreparedStatement;
use NoreSources\SQL\DBMS\TransactionBlockException;
use NoreSources\SQL\DBMS\TransactionBlockInterface;
use NoreSources\SQL\Expression\CastFunction;
use NoreSources\SQL\Expression\Literal;
use NoreSources\SQL\Expression\Parameter;
use NoreSources\SQL\Expression\TimestampFormatFunction;
use NoreSources\SQL\Result\InsertionStatementResultInterface;
use NoreSources\SQL\Result\Recordset;
use NoreSources\SQL\Statement\Manipulation\DeleteQuery;
use NoreSources\SQL\Statement\Manipulation\InsertQuery;
use NoreSources\SQL\Statement\Manipulation\UpdateQuery;
use NoreSources\SQL\Statement\Query\SelectQuery;
use NoreSources\SQL\Statement\Structure\CreateTableQuery;
use NoreSources\SQL\Statement\Structure\DropTableQuery;
use NoreSources\SQL\Structure\ArrayColumnDescription;
use NoreSources\SQL\Structure\StructureElementInterface;
use NoreSources\SQL\Structure\TableStructure;
use NoreSources\Test\DatasourceManager;
use NoreSources\Test\DerivedFileManager;
use NoreSources\Test\Generator;
use NoreSources\Test\TestConnection;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

class DBMSTestSilentLogger implements LoggerInterface, \Countable
{
	use LoggerTrait;
	use SingletonTrait;

	public function __construct()
	{
		$this->logs = [];
	}

	public function count()
	{
		return \count($this->logs);
	}

	public function clear()
	{
		$this->logs = [];
	}

	public function log($level, $message, $context = array())
	{
		$this->logs[] = [
			$level,
			$message
		];
	}

	/**
	 *
	 * @var array
	 */
	public $logs;
}

final class DBMSCommonTest extends TestCase
{

	public function __construct($name = null, array $data = [],
		$dataName = '')
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
		$this->assertInstanceOf(ConnectionInterface::class, $connection,
			$dbmsName);
		$this->assertTrue($connection->isConnected(), $dbmsName);

		$structure = $this->structures->get('types');
		$this->assertInstanceOf(StructureElementInterface::class,
			$structure);
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
			// ,
			// 'timestamp_tz' => [
			// 'expected' => new \DateTime('2010-11-12T13:14:15+0100')
			// ]
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
			// 'timestamp_tz' => [
			// 'insert' => new \DateTime('2010-11-12T13:14:15+0400'),
			// 'expected' => new \DateTime('2010-11-12T13:14:15+0400')
			// ]
			]
		];

		$rowQueries = [];

		foreach ($rows as $label => $columns)
		{
			/**
			 *
			 * @var \NoreSources\SQL\Statement\Manipulation\InsertQuery $q
			 */
			$q = $connection->getStatementFactory()->newStatement(
				K::QUERY_INSERT);
			$q->table($tableStructure);
			foreach ($columns as $columnName => $specs)
			{
				if (Container::keyExists($specs, 'insert'))
				{
					$as = $q->setColumnValue($columnName,
						$specs['insert'],
						Container::keyValue($specs, 'evaluate', false));
				}
			}

			$data = ConnectionHelper::buildStatement($connection, $q,
				$tableStructure);
			$rowQueries[$label] = \strval($data);
			$result = $connection->executeStatement($data);

			$this->assertInstanceOf(
				InsertionStatementResultInterface::class, $result,
				$label);
		}

		$q = new SelectQuery($tableStructure);
		$q->orderBy('int');
		$data = ConnectionHelper::buildStatement($connection, $q,
			$tableStructure);

		$recordset = $connection->executeStatement($data);
		$this->assertInstanceOf(Recordset::class, $recordset, $dbmsName);
		$recordset->setFlags(
			$recordset->getFlags() | Recordset::FETCH_UNSERIALIZE);

		if ($recordset instanceof \Countable)
		{
			$this->assertCount(\count($rows), $recordset,
				$dbmsName . ' ' . $label .
				' record count (Countable interface)');
		}
		else
		{
			$c = clone $q;
			$c->columns([
				'count (base)' => 'c'
			]);
			$cd = ConnectionHelper::buildStatement($connection, $c,
				$tableStructure);
			$cr = $connection->executeStatement($cd);
			$this->assertInstanceOf(Recordset::class, $cr,
				$dbmsName . ' select count ()');

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
					$dbmsName . ':' . $index . ':' . $label . ':' .
					$columnName . ' value' . PHP_EOL .
					$rowQueries[$label]);
			}
		}

		$this->assertEquals(\count($rows), $count,
			'Recordset count (iterate)');

		// Binary data insertion
		{
			$i = new InsertQuery($tableStructure);
			$fileName = 'binary-content.data';
			$content = file_get_contents(__DIR__ . '/data/' . $fileName);
			$i['base'] = $fileName;
			$i['binary'] = $content;

			$result = $connection->executeStatement(
				ConnectionHelper::buildStatement($connection, $i,
					$tableStructure));

			$this->assertInstanceOf(
				InsertionStatementResultInterface::class, $result,
				$fileName . ' binary insert');

			$s = new SelectQuery($tableStructure);
			$s->orderBy('int');
			$s->columns('binary');
			$s->where([
				'base' => new Literal($fileName)
			]);

			$result = $connection->executeStatement(
				ConnectionHelper::buildStatement($connection, $s,
					$tableStructure));

			$this->assertInstanceOf(Recordset::class, $result,
				$dbmsName . ' ' . $fileName . ' select');

			$result->setFlags(
				$result->getFlags() | Recordset::FETCH_UNSERIALIZE);
			if ($result instanceof \Countable)
				$this->assertCount(1, $result,
					$dbmsName . ' ' . $fileName . ' count');

			$row = $result->current();

			$this->assertEquals($content, $row['binary'],
				$dbmsName . ' ' . $fileName . ' content from db');
		}
	}

	public function testTimestampFormats()
	{
		$settings = $this->connections->getAvailableConnectionNames();

		foreach ($settings as $dbmsName)
		{
			$connection = $this->connections->get($dbmsName);
			$this->assertInstanceOf(ConnectionInterface::class,
				$connection, $dbmsName);
			$this->assertTrue($connection->isConnected(), $dbmsName);
			$structure = $this->structures->get('types');
			$this->assertInstanceOf(StructureElementInterface::class,
				$structure);
			$tableStructure = $structure['ns_unittests']['types'];
			$this->assertInstanceOf(TableStructure::class,
				$tableStructure);

			$this->recreateTable($connection, $tableStructure);
			$this->dbmsTimestampFormats($connection, $tableStructure);
		}
	}

	public function dbmsTimestampFormats(
		ConnectionInterface $connection, TableStructure $tableStructure)
	{
		$dbmsName = \preg_replace('/Connection/', '',
			TypeDescription::getLocalName($connection));

		$method = __CLASS__ . '::' . debug_backtrace()[1]['function'];

		$timestamps = [];
		for ($i = 0; $i < 10; $i++)
		{
			$timestamps[] = Generator::randomDateTime(
				[
					'yearRange' => [
						1789,
						2049
					],

					'timezone' => DateTime::getUTCTimezone()
				]);
		}

		// Some static timestamps
		$timestamps['UNIX epoch'] = new DateTIme('@0',
			DateTIme::getUTCTimezone());

		$timestamps['A year where "Y" (1806) and "o" (1807) differ'] = new DateTime(
			'1806-12-29T23:02:01+0000');

		$formats = DateTime::getFormatTokenDescriptions();
		$formats['Y-m-d'] = 'Date';
		$formats['H:i:s'] = 'Time';

		$delete = ConnectionHelper::prepareStatement($connection,
			new DeleteQuery($tableStructure), $tableStructure);

		$columnType = new ArrayColumnDescription(
			[
				K::COLUMN_DATA_TYPE => K::DATATYPE_DATETIME
			]);

		foreach ($formats as $format => $desc)
		{
			$label = $desc;
			if (Container::isArray($desc))
			{
				$label = Container::keyValue($desc,
					DateTime::FORMAT_LABEL, $format);
				if (Container::keyExists($desc, DateTime::FORMAT_DETAILS))
					$label .= ' (' . $desc[DateTime::FORMAT_DETAILS] .
						')';
				if (Container::keyExists($desc, DateTime::FORMAT_RANGE))
					$label .= ' [' .
						implode('-', $desc[DateTime::FORMAT_RANGE]) . ']';
			}
			$select = new SelectQuery();
			$select->columns(
				[
					new TimestampFormatFunction($format,
						new CastFunction(new Parameter('timestamp'),
							$columnType)),
					'format'
				]);

			DBMSTestSilentLogger::getInstance()->clear();
			$connection->setLogger(DBMSTestSilentLogger::getInstance());
			$select = ConnectionHelper::prepareStatement($connection,
				$select);
			$connection->setLogger(ErrorReporterLogger::getInstance());

			$this->assertInstanceOf(PreparedStatement::class, $select,
				$dbmsName . ' ' . $method . ' SELECT');

			$this->assertCount(1, $select->getParameters(),
				'Number of parameters of SELECT');

			if (DBMSTestSilentLogger::getInstance()->count())
				continue;

			$this->derivedFileManager->assertDerivedFile(
				\strval($select) . PHP_EOL, $method,
				$dbmsName . '_' . $format, 'sql');

			foreach ($timestamps as $test => $dateTime)
			{
				if (!($dateTime instanceof \DateTimeInterface))
					$dateTime = new DateTime($dateTime,
						DateTIme::getUTCTimezone());
				$expected = $dateTime->format($format);

				$this->connections->queryTest($connection,
					[
						'format' => $expected
					],
					[
						'select' => [
							$select,
							[
								'timestamp' => $dateTime
							]
						],
						'label' => $dateTime->format(\DateTime::ISO8601) .
						': ' . $dbmsName . ' [' . $format . '] ' . $label
					]);
			}
		}
	}

	public function testParametersTypes()
	{
		$settings = $this->connections->getAvailableConnectionNames();

		foreach ($settings as $dbmsName)
		{
			$connection = $this->connections->get($dbmsName);
			$this->assertInstanceOf(ConnectionInterface::class,
				$connection, $dbmsName);
			$this->assertTrue($connection->isConnected(), $dbmsName);

			$structure = $this->structures->get('types');
			$this->assertInstanceOf(StructureElementInterface::class,
				$structure);
			$tableStructure = $structure['ns_unittests']['types'];
			$this->assertInstanceOf(TableStructure::class,
				$tableStructure);

			$this->recreateTable($connection, $tableStructure);
			$this->dbmsParametersTypes($connection, $tableStructure);
		}
	}

	private function dbmsParametersTypes(
		ConnectionInterface $connection, TableStructure $tableStructure)
	{
		$dbmsName = TypeDescription::getLocalName($connection);
		$method = __CLASS__ . '::' . debug_backtrace()[1]['function'];
		/**
		 *
		 * @var \NoreSources\SQL\Statement\Manipulation\InsertQuery $i
		 */
		$i = $connection->getStatementFactory()->newStatement(
			K::QUERY_INSERT);
		$i->table($tableStructure);
		$i('int', ':even');
		$i('large_int', ':odd');
		$i('small_int', ':even');

		$select = ConnectionHelper::buildStatement($connection,
			new SelectQuery($tableStructure), $tableStructure);

		$delete = ConnectionHelper::buildStatement($connection,
			new DeleteQuery($tableStructure), $tableStructure);

		$insert = ConnectionHelper::prepareStatement($connection, $i,
			$tableStructure);
		$sql = \SqlFormatter::format(\strval($insert), false);
		$this->derivedFileManager->assertDerivedFile($sql, $method,
			$dbmsName . '_insert', 'sql');

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
			$this->connections->queryTest($connection, $test['expected'],
				[
					'insert' => [
						$insert,
						$test['parameters']
					],
					'select' => $select,
					'cleanup' => $delete
				]);
		}

		$insert = new InsertQuery($tableStructure);
		$insert('binary', ':bin');
		$insert = ConnectionHelper::prepareStatement($connection,
			$insert, $tableStructure);

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
			$this->connections->queryTest($connection, $test['expected'],
				[
					'insert' => [
						$insert,
						$test['parameters']
					],
					'select' => $select,
					'cleanup' => $delete
				]);
		}

		$insert = new InsertQuery($tableStructure);
		$insert('binary', ':bin');
		$insert('base', ':base');
		$insert('large_int', ':int');
		$insert = ConnectionHelper::prepareStatement($connection,
			$insert, $tableStructure);

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
			$this->connections->queryTest($connection, $test['expected'],
				[
					'insert' => [
						$insert,
						$test['parameters']
					],
					'select' => $select,
					'cleanup' => $delete
				]);
		}
	}

	public function testTransaction()
	{
		$settings = $this->connections->getAvailableConnectionNames();
		foreach ($settings as $dbmsName)
		{
			$connection = $this->connections->get($dbmsName);
			$this->assertInstanceOf(ConnectionInterface::class,
				$connection, $dbmsName);
			$this->assertTrue($connection->isConnected(), $dbmsName);

			if (true)
			{
				$b = $connection->newTransactionBlock(
					'A lonely transaction');
				$this->assertInstanceOf(
					TransactionBlockInterface::class, $b);
				$b->commit();
				$this->assertEquals(K::TRANSACTION_STATE_COMMITTED,
					$b->getBlockState(), 'Lonely transaction state');
			}

			$structure = $this->structures->get('keyvalue');
			$this->assertInstanceOf(StructureElementInterface::class,
				$structure);
			$tableStructure = $structure['ns_unittests']['keyvalue'];
			$this->assertInstanceOf(TableStructure::class,
				$tableStructure);

			$this->recreateTable($connection, $tableStructure);
			$this->connectionTransactionTest($connection,
				$tableStructure);
		}
	}

	private function connectionTransactionTest(
		ConnectionInterface $connection, TableStructure $tableStructure)
	{
		$dbmsName = TypeDescription::getLocalName($connection);

		$insert = new InsertQuery($tableStructure);
		$insert('id', ':id');
		$insert('text', ':text');
		$insert = ConnectionHelper::prepareStatement($connection,
			$insert, $tableStructure);

		/**
		 *
		 * @var UpdateQuery $update
		 */
		$update = $connection->getStatementFactory()->newStatement(
			K::QUERY_UPDATE);
		$update->table($tableStructure);
		$update('text', ':text');
		$update->where([
			'id' => ':id'
		]);
		$update = ConnectionHelper::prepareStatement($connection,
			$update, $tableStructure);

		$select = new SelectQuery();
		$select->from($tableStructure)->where('id = :id');
		$select = ConnectionHelper::prepareStatement($connection,
			$select, $tableStructure);

		for ($i = 1; $i <= 5; $i++)
		{
			$blocks = [];
			$initialValue = 'initial value';
			$values = [
				$initialValue
			];
			$states = [
				(rand() % 2) == 0,
				(rand() % 2) == 0,
				(rand() % 2) == 0
			];
			$stateCount = \count($states);

			$text = $this->connections->getRowValue($connection, $select,
				'text', [
					'id' => $i
				]);

			$statement = ($text === null) ? $insert : $update;
			$result = $connection->executeStatement($statement,
				[
					'id' => $i,
					'text' => $initialValue
				]);

			for ($b = 0; $b < $stateCount; $b++)
			{
				$blocks[$b] = $connection->newTransactionBlock(
					'block_' . $i . '_' . ($b + 1));
				$this->assertInstanceOf(
					TransactionBlockInterface::class, $blocks[$b],
					$dbmsName . ' pass #' . ($i) . ' block ' . ($b + 1) .
					' instance');
				$values[] = $blocks[$b]->getBlockName();
				$result = $connection->executeStatement($update,
					[
						'id' => $i,
						'text' => $blocks[$b]->getBlockName()
					]);
			}

			for ($b = 0; $b < $stateCount; $b++)
			{
				$this->assertEquals(
					TransactionBlockInterface::STATE_PENDING,
					$blocks[$b]->getBlockState(),
					$dbmsName . ' pass # ' . ($i) . ' block ' . ($b + 1) .
					' state');
			}

			$indexes = [];
			for ($b = 0; $b < $stateCount; $b++)
				$indexes[] = $b;

			$operations = [];

			while (($c = Container::count($indexes)))
			{
				$index = rand() % $c;
				$b = $indexes[$index];
				Container::removeKey($indexes, $index);
				$indexes = \array_values($indexes);

				$operations[] = [
					'block' => $b,
					'commit' => $states[$b],
					'change' => ($blocks[$b]->getBlockState() ==
					TransactionBlockInterface::STATE_PENDING)
				];

				try
				{
					if ($states[$b])
						$blocks[$b]->commit();
					else
						$blocks[$b]->rollback();
				}
				catch (TransactionBlockException $e)
				{
					if ($e->getCode() !=
						TransactionBlockException::INVALID_STATE)
						throw $e;
				}
			}

			$max = $stateCount;
			foreach ($operations as $operation)
			{
				if ($operation['change'] && !$operation['commit'] &&
					$operation['block'] < $max)
				{
					$max = $operation['block'];
				}
			}

			$expoected = $values[$max];

			$operations = Container::implodeValues($operations, ', ',
				function ($operation) use ($blocks) {
					$s = ($operation['commit'] ? 'commit' : 'rollback') .
					' ' . $blocks[$operation['block']]->getBlockName();
					if (!$operation['change'])
						$s .= ' (no-op)';
					return $s;
				});

			$text = $this->connections->getRowValue($connection, $select,
				'text', [
					'id' => $i
				]);

			$this->assertEquals($expoected, $text,
				$dbmsName . ' pass # ' . ($i) . ' execution of ' .
				$stateCount . ' randomly committed/rolledback blocks (' .
				$operations . ')');
		}
	}

	public function testParametersEmployees()
	{
		$structure = $this->structures->get('Company');
		$tableStructure = $structure['ns_unittests']['Employees'];
		$this->assertInstanceOf(Structure\TableStructure::class,
			$tableStructure);

		$settings = $this->connections->getAvailableConnectionNames();

		foreach ($settings as $dbmsName)
		{
			$connection = $this->connections->get($dbmsName);
			$this->assertInstanceOf(ConnectionInterface::class,
				$connection, $dbmsName);
			$this->assertTrue($connection->isConnected(), $dbmsName);

			$this->employeesTest($tableStructure, $connection);
		}
	}

	private function employeesTest(TableStructure $tableStructure,
		ConnectionInterface $connection)
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

		$preparedInsert = ConnectionHelper::prepareStatement(
			$connection, $insertQuery, $tableStructure);

		$this->assertInstanceOf(PreparedStatement::class,
			$preparedInsert, $dbmsName);

		$this->assertEquals(3,
			$preparedInsert->getParameters()
				->count(), 'Number of parameters in prepared statement');

		$sql = strval($preparedInsert);
		$sql = \SqlFormatter::format(strval($sql), false);
		$this->derivedFileManager->assertDerivedFile($sql, __METHOD__,
			$dbmsName . '_insert', 'sql');

		$p = [
			'nameValue' => 'Bob',
			'salaryValue' => 2000,
			'identifier' => 1
		];

		$result = $connection->executeStatement($preparedInsert, $p);
		$this->assertInstanceOf(
			InsertionStatementResultInterface::class, $result,
			$dbmsName . ' ' . $preparedInsert);

		$p['identifier'] = 2;
		$p['nameValue'] = 'Ron';
		$result = $connection->executeStatement($preparedInsert, $p);
		$this->assertInstanceOf(
			InsertionStatementResultInterface::class, $result,
			$dbmsName . ' ' . $preparedInsert);

		// Test result column count when no column are specified (select * from ...)
		$basicSelectQuery = new SelectQuery($tableStructure);
		$preparedBasicSelect = ConnectionHelper::prepareStatement(
			$connection, $basicSelectQuery, $tableStructure);
		$this->assertInstanceOf(PreparedStatement::class,
			$preparedBasicSelect, $dbmsName);

		$this->assertCount(4, $preparedBasicSelect->getResultColumns(),
			$dbmsName .
			' Prepared statement result columns count (auto-detected)');

		$selectColumnQuery = new SelectQuery($tableStructure);
		$selectColumnQuery->columns('name', 'gender', 'salary');

		$preparedSelectColumn = ConnectionHelper::prepareStatement(
			$connection, $selectColumnQuery, $tableStructure);
		$this->assertInstanceOf(PreparedStatement::class,
			$preparedSelectColumn, $dbmsName);
		$this->assertCount(3, $preparedSelectColumn->getResultColumns(),
			$dbmsName . ' Prepared statement result columns count');

		$result = $connection->executeStatement($preparedSelectColumn);
		$this->assertInstanceOf(Recordset::class, $result, $dbmsName,
			$preparedSelectColumn);

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

		list ($_, $expectedResultColumnKeys) = Container::first(
			$expected);
		$index = 0;
		foreach ($expectedResultColumnKeys as $name => $_)
		{
			$this->assertTrue(
				$result->getResultColumns()
					->hasColumn($index),
				$dbmsName . ' column #' . $index . ' exists');

			$this->assertTrue(
				$result->getResultColumns()
					->hasColumn($name),
				$dbmsName . ' column "' . $name . '" exists');

			$byIndex = $result->getResultColumns()->getColumn($index);
			$this->assertEquals($name, $byIndex->name,
				$dbmsName . ' Recordset result column #' . $index);

			$byName = $result->getResultColumns()->getColumn($name);
			$this->assertEquals($name, $byName->name,
				$dbmsName . ' Recordset result column "' . $name . '"');

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
		$backedSelectByName = ConnectionHelper::buildStatement(
			$connection, $selectByNameParamQuery, $tableStructure);

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
			$result = $connection->executeStatement($backedSelectByName,
				$params);

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
					$dbmsName . ' Number of row of ' . $testName .
					'(pass ' . $pass . ')');
			}
		}
	}

	private function recreateTable(ConnectionInterface $connection,
		TableStructure $tableStructure)
	{
		$dbmsName = TypeDescription::getLocalName($connection);

		$builder = $connection->getStatementBuilder();
		$builderFlags = $builder->getBuilderFlags(
			K::BUILDER_DOMAIN_GENERIC);

		$createBuilderFlags = ($builderFlags |
			$builder->getBuilderFlags(K::BUILDER_DOMAIN_CREATE_TABLE));

		$dropBuilderFlags = $builderFlags |
			$builder->getBuilderFlags(K::BUILDER_DOMAIN_DROP_TABLE);

		if (1) // (($createBuilderFlags & K::BUILDER_CREATE_REPLACE) == 0)
		{

			try // PostgreSQL < 8.2 does not support DROP IF EXISTS and may fail
			{
				$drop = $connection->getStatementFactory()->newStatement(
					K::QUERY_DROP_TABLE);
				if ($drop instanceof DropTableQuery)
					$drop->flags(DropTableQuery::CASCADE)->table(
						$tableStructure);
				$data = ConnectionHelper::buildStatement($connection,
					$drop, $tableStructure);
				$connection->executeStatement($data);
			}
			catch (ConnectionException $e)
			{
				if ($dropBuilderFlags & K::BUILDER_IF_EXISTS)
					throw $e;
			}
		}

		$factory = $connection->getStatementFactory();

		/**
		 *
		 * @var CreateTableQuery $createTable
		 */
		$createTable = $factory->newStatement(K::QUERY_CREATE_TABLE,
			$tableStructure);
		$createTable->flags(K::BUILDER_CREATE_REPLACE);
		$result = false;
		$data = ConnectionHelper::buildStatement($connection,
			$createTable, $tableStructure);
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
				TypeDescription::getName($connection) . PHP_EOL .
				\strval($data) . ': ' . $e->getMessage());
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