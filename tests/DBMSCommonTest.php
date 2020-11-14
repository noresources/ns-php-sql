<?php
namespace NoreSources\SQL;

use NoreSources\Container;
use NoreSources\DateTime;
use NoreSources\SemanticVersion;
use NoreSources\SingletonTrait;
use NoreSources\TypeConversion;
use NoreSources\TypeDescription;
use NoreSources\MediaType\MediaType;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\ConnectionException;
use NoreSources\SQL\DBMS\ConnectionInterface;
use NoreSources\SQL\DBMS\PlatformInterface;
use NoreSources\SQL\DBMS\PreparedStatementInterface;
use NoreSources\SQL\DBMS\TransactionBlockException;
use NoreSources\SQL\DBMS\TransactionBlockInterface;
use NoreSources\SQL\DBMS\TransactionInterface;
use NoreSources\SQL\DBMS\TypeInterface;
use NoreSources\SQL\DBMS\MySQL\MySQLPlatform;
use NoreSources\SQL\DBMS\PDO\PDOConnection;
use NoreSources\SQL\DBMS\PDO\PDOPlatform;
use NoreSources\SQL\DBMS\PostgreSQL\PostgreSQLPlatform;
use NoreSources\SQL\DBMS\SQLite\SQLitePlatform;
use NoreSources\SQL\DBMS\Types\ArrayObjectType;
use NoreSources\SQL\Expression\CastFunction;
use NoreSources\SQL\Expression\ColumnDeclaration;
use NoreSources\SQL\Expression\Data;
use NoreSources\SQL\Expression\Parameter;
use NoreSources\SQL\Expression\TimestampFormatFunction;
use NoreSources\SQL\Result\InsertionStatementResultInterface;
use NoreSources\SQL\Result\Recordset;
use NoreSources\SQL\Statement\ParameterData;
use NoreSources\SQL\Statement\StatementBuilder;
use NoreSources\SQL\Statement\StatementTokenStreamContext;
use NoreSources\SQL\Statement\Manipulation\DeleteQuery;
use NoreSources\SQL\Statement\Manipulation\InsertQuery;
use NoreSources\SQL\Statement\Manipulation\UpdateQuery;
use NoreSources\SQL\Statement\Query\SelectQuery;
use NoreSources\SQL\Statement\Structure\CreateNamespaceQuery;
use NoreSources\SQL\Statement\Structure\CreateTableQuery;
use NoreSources\SQL\Statement\Structure\DropTableQuery;
use NoreSources\SQL\Structure\ArrayColumnDescription;
use NoreSources\SQL\Structure\ColumnStructure;
use NoreSources\SQL\Structure\NamespaceStructure;
use NoreSources\SQL\Structure\PrimaryKeyTableConstraint;
use NoreSources\SQL\Structure\StructureElementInterface;
use NoreSources\SQL\Structure\TableStructure;
use NoreSources\Test\ConnectionHelper;
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

	public function logInit()
	{
		echo (PHP_EOL);
	}

	public function logKeyValue($key, $value, $level = 0)
	{
		echo ($this->textKeyValue($key, $value, $level));
	}

	public function textKeyValue($key, $value, $level = 0)
	{
		$length = 32;
		$format = \str_repeat('  ', $level) . '%-' . $length . '.' .
			$length . 's: %s' . PHP_EOL;

		return \sprintf($format, $key, TypeConversion::toString($value));
	}

	public function testConnections()
	{
		$settings = $this->connections->getAvailableConnectionNames();

		if (Container::count($settings) == 0)
		{
			$this->assertTrue(true);
			return;
		}

		$this->logInit();

		foreach ($settings as $dbmsName)
		{
			$connection = $this->connections->get($dbmsName);
			$this->assertInstanceOf(ConnectionInterface::class,
				$connection, $dbmsName);

			$this->assertTrue($connection->isConnected(), $dbmsName);

			$platform = $connection->getPlatform();

			$this->assertInstanceOf(PlatformInterface::class, $platform,
				$dbmsName);

			$platform = $connection->getPlatform();

			$this->assertInstanceOf(PlatformInterface::class, $platform,
				$dbmsName . ' Connection provides Platform');

			$this->logKeyValue($dbmsName,
				TypeDescription::getLocalName($platform));

			$this->logKeyValue('Version',
				$platform->getPlatformVersion(), 1);
			$this->logKeyValue('Compability',
				$platform->getPlatformVersion(
					PlatformInterface::VERSION_COMPATIBILITY), 1);

			if ($connection instanceof PDOConnection)
			{
				foreach ([
					'driver' => \PDO::ATTR_DRIVER_NAME,
					'server' => \PDO::ATTR_SERVER_VERSION,
					'client' => \PDO::ATTR_CLIENT_VERSION
				] as $name => $attribute)
				{
					$this->logKeyValue($name,
						$connection->getPDOAttribute($attribute), 1);
				}
			}
		}
	}

	public function testTypeMapping()
	{
		$settings = $this->connections->getAvailableConnectionNames();

		foreach ($settings as $dbmsName)
		{
			$connection = $this->connections->get($dbmsName);

			if ($connection instanceof PDOConnection)
				continue;

			$this->dbmsTestTypeMapping($connection, $dbmsName);
		}
	}

	public function dbmsTestTypeMapping(ConnectionInterface $connection,
		$dbmsName)
	{
		$platform = $connection->getPlatform();

		$version = $platform->getPlatformVersion(
			K::PLATFORM_VERSION_COMPATIBILITY);
		$versionString = $version->slice(SemanticVersion::MAJOR,
			SemanticVersion::MINOR);

		$dbmsName .= '_' . $versionString;
		$method = $this->getMethodName();

		$context = new StatementTokenStreamContext($platform);
		$builder = new StatementBuilder();
		$tableWithPk = new TableStructure('table');
		$pk = new ColumnStructure('pk');
		$tableWithPk->addConstraint(
			new PrimaryKeyTableConstraint([
				$pk
			]));

		$tests = [
			'small binary with length' => [
				'properties' => [
					K::COLUMN_DATA_TYPE => K::DATATYPE_BINARY |
					K::DATATYPE_NULL,
					K::COLUMN_LENGTH => 2
				]
			],
			'binary without length' => [
				'properties' => [
					K::COLUMN_DATA_TYPE => K::DATATYPE_BINARY |
					K::DATATYPE_NULL
				]
			],
			'integer primary key' => [
				'properties' => [
					K::COLUMN_DATA_TYPE => K::DATATYPE_INTEGER
				],
				'primary' => true
			],
			'int. primary key auto increment' => [
				'properties' => [
					K::COLUMN_DATA_TYPE => K::DATATYPE_INTEGER,
					K::COLUMN_FLAGS => K::COLUMN_FLAG_AUTO_INCREMENT
				],
				'primary' => true
			],
			'int auto increment' => [
				'properties' => [
					K::COLUMN_DATA_TYPE => K::DATATYPE_INTEGER,
					K::COLUMN_FLAGS => K::COLUMN_FLAG_AUTO_INCREMENT
				]
			],
			'int. composite PK auto inc.' => [
				'properties' => [
					K::COLUMN_DATA_TYPE => K::DATATYPE_INTEGER,
					K::COLUMN_FLAGS => K::COLUMN_FLAG_AUTO_INCREMENT
				],
				'primary' => true,
				'table' => $tableWithPk
			],
			'type with dflt length' => [
				'properties' => [
					K::COLUMN_DATA_TYPE => (K::DATATYPE_NULL |
					K::DATATYPE_STRING)
				],
				'type' => new ArrayObjectType(
					[
						K::TYPE_NAME => 'bit',
						K::TYPE_FLAGS => K::TYPE_FLAG_LENGTH,
						K::TYPE_DEFAULT_LENGTH => 1,
						K::TYPE_MAX_LENGTH => 64
					])
			]
		];

		$declaractionClassInstance = $platform->newExpression(
			ColumnDeclaration::class);

		$content = '';
		$content .= $this->textKeyValue($dbmsName,
			'-- ' .
			TypeDescription::getLocalName($declaractionClassInstance));

		foreach ($tests as $label => $test)
		{

			$primary = Container::keyValue($test, 'primary', false);
			$table = Container::keyValue($test, 'table');
			if ($table)
			{
				$table = clone $table;
			}

			if ($primary)
			{
				if (!$table)
				{
					$table = new TableStructure('table');
				}
			}

			$column = new ColumnStructure('column', $table);
			if ($table)
				$table->appendElement($column);

			$this->assertEquals($table, $column->getParentElement(),
				$dbmsName . ' ' . $label . ' table');

			foreach ($test['properties'] as $k => $v)
				$column->setColumnProperty($k, $v);

			if ($primary)
			{
				/**
				 *
				 * @var PrimaryKeyTableConstraint $pkc
				 */
				$pkc = null;
				foreach ($table->getConstraints() as $c)
				{
					if ($c instanceof PrimaryKeyTableConstraint)
					{
						$pkc = $c;
						break;
					}
				}
				if (!$pkc)
				{
					$pkc = new PrimaryKeyTableConstraint([]);
					$table->addConstraint($pkc);
				}

				$pkc->append($column);
			}

			$flags = $column->getConstraintFlags();
			$this->assertEquals(
				($primary ? K::COLUMN_CONSTRAINT_PRIMARY_KEY : 0),
				($flags & K::COLUMN_CONSTRAINT_PRIMARY_KEY),
				$dbmsName . ' ' . $label . ' is' .
				($primary ? ' ' : ' not ') . 'part of a primary key');

			$type = Container::keyValue($test, 'type');
			if (!($type instanceof TypeInterface))
				$type = $platform->getColumnType($column);

			$this->assertInstanceOf(TypeInterface::class, $type,
				$dbmsName . ' ' . $label . ' column');

			$declaration = $platform->newExpression(
				ColumnDeclaration::class, $column, $type);
			$data = $builder->build($declaration, $context);
			$content .= $this->textKeyValue($label, \strval($data));
		}

		$this->derivedFileManager->assertDerivedFile($content, $method,
			$dbmsName, 'sql');
	}

	public function testTypeSerialization()
	{
		$settings = $this->connections->getAvailableConnectionNames();

		foreach ($settings as $dbmsName)
		{
			$connection = $this->connections->get($dbmsName);

			if ($connection instanceof PDOConnection)
				continue;

			$this->assertTrue($connection->isConnected(), $dbmsName);
			$this->dbmsTestTypeSerialization($connection, $dbmsName);
		}
	}

	public function dbmsTestTypeSerialization(
		ConnectionInterface $connection, $dbmsName)
	{
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
				/*
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
			$q = $connection->getPlatform()->newStatement(
				K::QUERY_INSERT);
			$q->table($tableStructure);
			foreach ($columns as $columnName => $specs)
			{
				if (Container::keyExists($specs, 'insert'))
				{
					$as = $q->setColumnData($columnName,
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
		$rowIterator = new \ArrayIterator($rows);
		foreach ($recordset as $index => $record)
		{
			$label = $rowIterator->key();
			$columns = $rowIterator->current();
			$rowIterator->next();
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
				'base' => new Data($fileName)
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
			if ($connection instanceof PDOConnection)
				continue;

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
		$dbmsName = $this->getDBMSName($connection);
		$method = $this->getMethodName();

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
					DateTime::FORMAT_DESCRIPTION_LABEL, $format);
				if (Container::keyExists($desc,
					DateTime::FORMAT_DESCRIPTION_DETAILS))
					$label .= ' (' .
						$desc[DateTime::FORMAT_DESCRIPTION_DETAILS] . ')';
				if (Container::keyExists($desc,
					DateTime::FORMAT_DESCRIPTION_RANGE))
					$label .= ' [' .
						implode('-',
							$desc[DateTime::FORMAT_DESCRIPTION_RANGE]) .
						']';
			}
			$select = $connection->getPlatform()->newStatement(
				K::QUERY_SELECT);

			$select->columns(
				[
					new TimestampFormatFunction($format,
						new CastFunction(new Parameter('timestamp'),
							$columnType)),
					'format'
				]);

			$validate = true;
			$translation = $connection->getPlatform()->getTimestampFormatTokenTranslation(
				$format);

			if (\is_array($translation)) // Fallback support
			{
				$validate = false;
				$translation = $translation[0];
			}

			if (!\is_string($translation))
				continue;

			$select = ConnectionHelper::prepareStatement($connection,
				$select);

			$this->assertInstanceOf(PreparedStatementInterface::class,
				$select, $dbmsName . ' ' . $method . ' SELECT');

			$this->assertCount(1, $select->getParameters(),
				'Number of parameters of SELECT');

			if (!($connection instanceof PDOConnection))
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
						': ' . $dbmsName . ' [' . $format . '] ' . $label,
						'assertValue' => $validate
					]);
			}
		}
	}

	public function testParameters()
	{
		$settings = $this->connections->getAvailableConnectionNames();

		foreach ($settings as $dbmsName)
		{
			$connection = $this->connections->get($dbmsName);
			if ($connection instanceof PDOConnection)
				continue;
			$this->assertTrue($connection->isConnected(), $dbmsName);
			$this->dbmsParameters($connection);
		}
	}

	private function dbmsParameters(ConnectionInterface $connection)
	{
		$dbmsName = $this->getDBMSName($connection);
		$method = $this->getMethodName();
		$platform = $connection->getPlatform();

		/**
		 *
		 * @var SelectQuery $subSelect
		 */
		$subSelect = $platform->newStatement(K::QUERY_SELECT);

		$subSelect->columns([
			'id' => 'classId'
		])
			->from('Classes', 'c')
			->where([
			'criteria' => ':param'
		]);

		$subData = ConnectionHelper::buildStatement($connection,
			$subSelect);
		$subSQL = \strval($subData);

		$this->derivedFileManager->assertDerivedFile($subSQL, $method,
			$dbmsName . '_subquery', 'sql');

		/**
		 *
		 * @var SelectQuery $mainSelect
		 */
		$mainSelect = $platform->newStatement(K::QUERY_SELECT);

		$mainSelect->columns([
			'classId' => 'c'
		])
			->from('Namespace', 'n')
			->where([
			'name' => ':nsname'
		])
			->where([
			'in' => [
				'classId',
				$subSelect
			]
		]);

		$mainData = ConnectionHelper::buildStatement($connection,
			$mainSelect);
		$mainSQL = \SqlFormatter::format(\strval($mainData), false);

		$mainParameters = $mainData->getParameters();
		$this->assertCount(2, $mainParameters,
			$dbmsName . ' parameter count');

		$firstParameter = $mainParameters->get(0);
		$this->assertEquals('nsname',
			$firstParameter[ParameterData::KEY],
			$dbmsName . ' first parameter key');
		$secondparameter = $mainParameters->get(1);
		$this->assertEquals('param',
			$secondparameter[ParameterData::KEY],
			$dbmsName . ' second parameter key');

		$this->derivedFileManager->assertDerivedFile($mainSQL, $method,
			$dbmsName . '_mainquery', 'sql');
	}

	public function testParametersTypes()
	{
		$settings = $this->connections->getAvailableConnectionNames();

		foreach ($settings as $dbmsName)
		{
			$connection = $this->connections->get($dbmsName);
			if ($connection instanceof PDOConnection)
				continue;
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
		$dbmsName = $this->getDBMSName($connection);
		$method = $this->getMethodName();
		$platform = $connection->getPlatform();
		/**
		 *
		 * @var \NoreSources\SQL\Statement\Manipulation\InsertQuery $i
		 */
		$i = $connection->getPlatform()->newStatement(K::QUERY_INSERT);
		$i->table($tableStructure);
		$i('int', ':even');
		$i('large_int', ':odd');
		$i('small_int', ':even');

		$select = $platform->newStatement(K::QUERY_SELECT,
			$tableStructure);
		$select = ConnectionHelper::buildStatement($connection, $select,
			$tableStructure);

		$delete = ConnectionHelper::buildStatement($connection,
			new DeleteQuery($tableStructure), $tableStructure);

		$insert = ConnectionHelper::prepareStatement($connection, $i,
			$tableStructure);
		$sql = \SqlFormatter::format(\strval($insert), false);
		if (!($connection instanceof PDOConnection))
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

			if (!($connection instanceof TransactionInterface))
				continue;

			$b = $connection->newTransactionBlock(
				'A lonely transaction');
			$this->assertInstanceOf(TransactionBlockInterface::class, $b);
			$b->commit();
			$this->assertEquals(K::TRANSACTION_STATE_COMMITTED,
				$b->getBlockState(), 'Lonely transaction state');

			$structure = $this->structures->get('keyvalue');
			$this->assertInstanceOf(StructureElementInterface::class,
				$structure);
			$tableStructure = $structure['ns_unittests']['keyvalue'];
			$this->assertInstanceOf(TableStructure::class,
				$tableStructure);

			if ($connection instanceof PDOConnection)
				continue;

			$this->recreateTable($connection, $tableStructure);
			$this->connectionTransactionTest($connection,
				$tableStructure);
		}
	}

	private function connectionTransactionTest(
		ConnectionInterface $connection, TableStructure $tableStructure)
	{
		$dbmsName = $this->getDBMSName($connection);

		$insert = new InsertQuery($tableStructure);
		$insert('id', ':id');
		$insert('text', ':text');
		$insert = ConnectionHelper::prepareStatement($connection,
			$insert, $tableStructure);

		/**
		 *
		 * @var UpdateQuery $update
		 */
		$update = $connection->getPlatform()->newStatement(
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

	public function testEmployeesTable()
	{
		$structure = $this->structures->get('Company');
		$tableStructure = $structure['ns_unittests']['Employees'];
		$this->assertInstanceOf(Structure\TableStructure::class,
			$tableStructure);

		$settings = $this->connections->getAvailableConnectionNames();

		foreach ($settings as $dbmsName)
		{
			$connection = $this->connections->get($dbmsName);

			$this->dbmsEmployeesTable($tableStructure, $connection);
		}
	}

	private function dbmsEmployeesTable(TableStructure $tableStructure,
		ConnectionInterface $connection)
	{
		$dbmsName = $this->getDBMSName($connection);
		$method = $this->getMethodName();
		$this->recreateTable($connection, $tableStructure);

		// Insert QUery
		$insertQuery = new InsertQuery($tableStructure);
		$insertQuery->setColumnData('id', ':identifier', true);
		$insertQuery['gender'] = 'M';
		$insertQuery('name', ':nameValue');
		$insertQuery('salary', ':salaryValue');

		$preparedInsert = ConnectionHelper::prepareStatement(
			$connection, $insertQuery, $tableStructure);

		$this->assertInstanceOf(PreparedStatementInterface::class,
			$preparedInsert, $dbmsName);

		$this->assertEquals(3,
			$preparedInsert->getParameters()
				->count(), 'Number of parameters in prepared statement');

		$sql = strval($preparedInsert);

		$sql = \SqlFormatter::format(strval($sql), false);
		if (!($connection instanceof PDOConnection))
			$this->derivedFileManager->assertDerivedFile($sql, $method,
				$dbmsName . '_insert', 'sql');

		$p = [
			'nameValue' => 'Bob',
			'salaryValue' => 2000,
			'identifier' => 1
		];

		try
		{
			$result = $connection->executeStatement($preparedInsert, $p);
		}
		catch (\Exception $e)
		{
			$this->assertTrue(false,
				$dbmsName . ' ' . $preparedInsert . ' ' .
				\var_export($p, true) . ': ' . $e->getMessage());
		}

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

		/**
		 *
		 * @var SelectQuery $basicSelectQuery
		 */
		$basicSelectQuery = $connection->getPlatform()->newStatement(
			K::QUERY_SELECT);

		$basicSelectQuery->from($tableStructure);

		$preparedBasicSelect = ConnectionHelper::prepareStatement(
			$connection, $basicSelectQuery, $tableStructure);
		$this->assertInstanceOf(PreparedStatementInterface::class,
			$preparedBasicSelect, $dbmsName);

		$expectedColumnCount = 4;

		$this->assertCount($expectedColumnCount,
			$preparedBasicSelect->getResultColumns(),
			$dbmsName .
			' Prepared statement result columns count (auto-detected)');

		/**
		 *
		 * @var SelectQuery $selectColumnQuery
		 */
		$selectColumnQuery = $connection->getPlatform()->newStatement(
			K::QUERY_SELECT);
		$selectColumnQuery->from($tableStructure);
		$selectColumnQuery->columns('name', 'gender', 'salary')->orderBy(
			'id');

		$preparedSelectColumn = ConnectionHelper::prepareStatement(
			$connection, $selectColumnQuery, $tableStructure);
		$this->assertInstanceOf(PreparedStatementInterface::class,
			$preparedSelectColumn, $dbmsName);
		$this->assertCount(3, $preparedSelectColumn->getResultColumns(),
			$dbmsName . ' Prepared statement result columns count');

		$lastTwo = [
			[
				'id' => 3,
				'name' => 'Alice',
				'gender' => 'F',
				'salary' => 2002.50
			],
			[
				'id' => 4,
				'name' => 'George Orwell',
				'gender' => 'M',
				'salary' => 1984
			]
		];

		foreach ($lastTwo as $row)
		{
			/**
			 *
			 * @var InsertQuery $q
			 */
			$q = $connection->getPlatform()->newStatement(
				K::QUERY_INSERT);
			$q->into($tableStructure);
			foreach ($row as $name => $value)
				$q->setColumnData($name, new Data($value));

			$r = $connection->executeStatement(
				ConnectionHelper::prepareStatement($connection, $q,
					$tableStructure));

			$this->assertInstanceOf(
				InsertionStatementResultInterface::class, $r);
		}

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

		$expected = \array_merge($expected,
			\array_map(
				function ($row) {
					return Container::filter($row,
						function ($k, $v) {
							return $k != 'id';
						});
				}, $lastTwo));

		$this->assertCount(4, $expected, 'Expected result count');
		$expectedRowCount = \count($expected);

		if ($result instanceof \Countable)
			$this->assertEquals($expectedRowCount, $result->count(),
				$dbmsName . ' result row count');

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

		// Test recordset rewind
		for ($pass = 1; $pass <= 2; $pass++)
		{
			$index = 0;
			foreach ($result as $row)
			{
				foreach ($expected[$index] as $name => $value)
				{
					$this->assertEquals($value, $row[$name],
						$dbmsName . ' Pass ' . $pass . ' Row ' . $index .
						' column ' . $name);
				}

				$index++;
			}
		}

		/*
		 * Use iterator interface manually
		 */
		{
			$rowCount = 0;
			$result->rewind();
			$result->setFlags(
				K::RECORDSET_FETCH_ASSOCIATIVE |
				K::RECORDSET_FETCH_UBSERIALIZE);

			while ($result->valid())
			{
				$row = $result->current();
				$this->assertEquals($expected[$rowCount], $row,
					$dbmsName . ' ' . \strval($preparedSelectColumn) .
					' using Iterator interface; row ' . $rowCount .
					' content');
				$result->next();
				$rowCount++;
			}

			$this->assertEquals($expectedRowCount, $rowCount,
				$dbmsName . ' using Iterator interface; row count');
		}

		{
			/*
			 * Ensure recorrdset based on the same premared statement
			 * are not linked together
			 *
			 */

			$results = [];
			$results[] = $connection->executeStatement(
				$preparedSelectColumn);
			$results[] = $connection->executeStatement(
				$preparedSelectColumn);

			for ($i = 0; $i < 2; $i++)
			{
				$results[$i]->setFlags(
					K::RECORDSET_FETCH_ASSOCIATIVE |
					K::RECORDSET_FETCH_UBSERIALIZE);
				$results[$i]->rewind();
				if ($results[$i] instanceof \Countable)
					$this->assertCount($expectedRowCount, $results[$i]);
			}

			$this->assertTrue($results[0]->valid(),
				$dbmsName . ' manual rewind');
			$this->assertEquals($expected[0], $results[0]->current(),
				$dbmsName . ' manual rewind');
			$results[0]->next();
			$this->assertTrue($results[0]->valid(),
				$dbmsName . ' manual next() 1');
			$this->assertEquals($expected[1], $results[0]->current(),
				$dbmsName . ' manual next() 1');
			$results[0]->next();

			$expectedRowIndexes = [
				2,
				0
			];
			$rowCount = [
				0,
				0
			];

			while (($results[0]->valid() || $results[1]->valid()))
			{
				for ($i = 0; $i < 2; $i++)
				{
					if (!$results[$i]->valid())
						continue;

					$expectedRow = $expected[$expectedRowIndexes[$i]];
					$row = $results[$i]->current();

					$this->assertEquals($expectedRow, $row,
						$dbmsName . ' recordset ' . $i . ' row ' .
						$expectedRowIndexes[$i]);

					$results[$i]->next();
					$expectedRowIndexes[$i]++;
					$rowCount[$i]++;
				}
			}

			$this->assertEquals($expectedRowCount - 2, $rowCount[0],
				$dbmsName . ' recordset 0 fetched row count');
			$this->assertEquals($expectedRowCount, $rowCount[1],
				$dbmsName . ' recordset 1 fetched row count');
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
						$dbmsName . ' Rows of ' . $testName . ' (pass ' .
						$pass . ')');
					$index++;
				}

				$this->assertEquals(count($test['rows']), $index,
					$dbmsName . ' Number of row of ' . $testName .
					'(pass ' . $pass . ')');
			}
		}
	}

	public function testMediaTypes()
	{
		$settings = $this->connections->getAvailableConnectionNames();
		$this->assertTrue(true, 'Silence tester');

		foreach ($settings as $dbmsName)
		{
			$connection = $this->connections->get($dbmsName);
			$this->dbmsMediaType($connection);
		}
	}

	private function dbmsMediaType(ConnectionInterface $connection)
	{
		$platform = $connection->getPlatform();
		$dbmsName = $this->getDBMSName($connection);

		$textColumn = new ColumnStructure('textColumn');

		$bitStringPlatforms = [
			PostgreSQLPlatform::class
			// MySQLPlatform::class
		];

		if (\in_array(TypeDescription::getName($platform),
			$bitStringPlatforms))
		{
			$bitStringColumn = new ColumnStructure('bitstring');
			$bitStringColumn->setColumnProperty(K::COLUMN_DATA_TYPE,
				K::DATATYPE_STRING);
			$bitStringColumn->setColumnProperty(K::COLUMN_MEDIA_TYPE,
				K::MEDIA_TYPE_BIT_STRING);

			$bitStringType = $platform->getColumnType($bitStringColumn);

			$this->assertInstanceOf(TypeInterface::class, $bitStringType,
				$dbmsName . ' A bit string type exists');

			$this->assertTrue($bitStringType->has(K::TYPE_MEDIA_TYPE),
				$dbmsName . ' type "' . $bitStringType->getTypeName() .
				'" has bitstring media type');

			foreach ([
				'bitstring' => [
					'101',
					'101'
				],
				'int' => [
					5,
					'101'
				]
			] as $label => $test)
			{
				$data = $test[0];
				$bitStringText = $test[1];

				$expected = $platform->serializeColumnData($textColumn,
					$bitStringText);
				$actual = $platform->serializeColumnData(
					$bitStringColumn, $data);

				$this->assertEquals($expected, $actual,
					$dbmsName . ' ' . $bitStringType->getTypeName() .
					' serialization');
			}
		}

		$jsonPlatforms = [
			MySQLPlatform::class,
			PostgreSQLPlatform::class,
			SQLitePlatform::class
		];

		if (\in_array(TypeDescription::getName($platform),
			$jsonPlatforms))
		{

			$jsonColumn = new ColumnStructure('jsonColumn');
			$jsonColumn->setColumnProperty(K::COLUMN_DATA_TYPE,
				K::DATATYPE_STRING);
			$jsonColumn->setColumnProperty(K::COLUMN_MEDIA_TYPE,
				MediaType::fromString('application/json'));

			$jsonType = $platform->getColumnType($jsonColumn);

			$this->assertInstanceOf(TypeInterface::class, $jsonType,
				$dbmsName . ' has a JSON type');

			$this->assertEquals('application/json',
				\strval($jsonType->get(K::TYPE_MEDIA_TYPE)),
				$jsonType->getTypeName() . ' media type');

			$tests = [
				'json string' => [
					'text',
					'"text"'
				],
				'json true' => [
					true,
					'true'
				],
				'json array' => [
					[
						'foo',
						'bar'
					],
					'["foo","bar"]'
				]
			];

			foreach ($tests as $label => $test)
			{
				$data = $test[0];
				$jsonText = $test[1];

				$expected = $platform->serializeColumnData($textColumn,
					$jsonText);
				$actual = $platform->serializeColumnData($jsonColumn,
					$data);

				$this->assertEquals($expected, $actual,
					$dbmsName . ' ' . $label);
			}
		}
	}

	private function recreateTable(ConnectionInterface $connection,
		TableStructure $tableStructure)
	{
		$dbmsName = $this->getDBMSName($connection);
		$method = $this->getMethodName(3);

		$platform = $connection->getPlatform();
		$factory = $connection->getPlatform();

		$parent = $tableStructure->getParentElement();
		if ($parent instanceof NamespaceStructure)
		{
			$nsExistanceCondition = $platform->queryFeature(
				[
					K::PLATFORM_FEATURE_CREATE,
					K::PLATFORM_FEATURE_NAMESPACE,
					K::PLATFORM_FEATURE_EXISTS_CONDITION
				], false);

			/**
			 *
			 * @var CreateNamespaceQuery
			 */
			$createNamespace = $factory->newStatement(
				K::QUERY_CREATE_NAMESPACE);

			try
			{
				if (!($createNamespace instanceof CreateNamespaceQuery))
					throw new \Exception(
						'not CREATE NAMESPACE query available');
				$createNamespace->identifier($parent->getName());

				$data = ConnectionHelper::buildStatement($connection,
					$createNamespace, $parent);
				$connection->executeStatement($data);
			}
			catch (ConnectionException $e)
			{}
		}

		$tableExistanceCondition = $platform->queryFeature(
			[
				K::PLATFORM_FEATURE_DROP,
				K::PLATFORM_FEATURE_EXISTS_CONDITION
			], false);

		try // PostgreSQL < 8.2 does not support DROP IF EXISTS and may fail
		{
			$drop = $connection->getPlatform()->newStatement(
				K::QUERY_DROP_TABLE);
			if ($drop instanceof DropTableQuery)
				$drop->flags(DropTableQuery::CASCADE)->table(
					$tableStructure);
			$data = ConnectionHelper::buildStatement($connection, $drop,
				$tableStructure);
			$connection->executeStatement($data);
		}
		catch (ConnectionException $e)
		{
			if ($tableExistanceCondition)
				throw $e;
		}

		/**
		 *
		 * @var CreateTableQuery $createTable
		 */
		$createTable = $factory->newStatement(K::QUERY_CREATE_TABLE,
			$tableStructure);

		$this->assertInstanceOf(CreateTableQuery::class, $createTable,
			$dbmsName . ' CreateTableQuery');
		$this->assertInstanceOf(TableStructure::class,
			$createTable->getStructure(),
			$dbmsName . ' CrateTableQuery table reference');

		$createTable->flags(
			$createTable->getFlags() | CreateTableQuery::REPLACE);
		$result = false;
		$data = ConnectionHelper::buildStatement($connection,
			$createTable, $tableStructure);
		$sql = \SqlFormatter::format(strval($data), false);
		if (!($connection instanceof PDOConnection))
			$this->derivedFileManager->assertDerivedFile($sql, $method,
				$dbmsName . '_create_' . $tableStructure->getName(),
				'sql');

		try
		{
			$result = $connection->executeStatement($data);
		}
		catch (\Exception $e)
		{
			$this->assertEquals(true, $result,
				'Create table ' . $tableStructure->getName() . ' on ' .
				TypeDescription::getLocalName($connection) . PHP_EOL .
				\strval($data) . ': ' . $e->getMessage());
		}

		$this->assertTrue($result,
			'Create table ' . $tableStructure->getName() . ' on ' .
			TypeDescription::getLocalName($connection));

		return $result;
	}

	private function getMethodName($backLevel = 2)
	{
		return __CLASS__ . '::' .
			debug_backtrace()[$backLevel]['function'];
	}

	private function getDBMSName(ConnectionInterface $connection)
	{
		$dbmsName = \preg_replace('/Connection/', '',
			TypeDescription::getLocalName($connection));

		$platform = $connection->getPlatform();
		if ($connection instanceof PDOPlatform)
		{
			$base = $platform->getBasePlatform();

			$baseName = \preg_replace('/Platform$/', '',
				TypeDescription::getLocalName($base));

			$dbmsName .= '_' . $base;
		}

		$version = $platform->getPlatformVersion(
			K::PLATFORM_VERSION_COMPATIBILITY);
		$versionString = $version->slice(SemanticVersion::MAJOR,
			SemanticVersion::MINOR);

		$dbmsName .= '_' . $versionString;

		return $dbmsName;
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
