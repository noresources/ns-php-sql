<?php
namespace NoreSources\SQL;

use NoreSources\SingletonTrait;
use NoreSources\Container\Container;
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
use NoreSources\SQL\DBMS\Configuration\ConfiguratorInterface;
use NoreSources\SQL\DBMS\Configuration\ConfiguratorProviderInterface;
use NoreSources\SQL\DBMS\Explorer\StructureExplorerProviderInterface;
use NoreSources\SQL\DBMS\MySQL\MySQLPlatform;
use NoreSources\SQL\DBMS\PDO\PDOConnection;
use NoreSources\SQL\DBMS\PostgreSQL\PostgreSQLPlatform;
use NoreSources\SQL\DBMS\SQLite\SQLitePlatform;
use NoreSources\SQL\DBMS\Types\ArrayObjectType;
use NoreSources\SQL\Result\InsertionStatementResultInterface;
use NoreSources\SQL\Result\Recordset;
use NoreSources\SQL\Structure\ColumnDescriptionInterface;
use NoreSources\SQL\Structure\ColumnStructure;
use NoreSources\SQL\Structure\ForeignKeyTableConstraint;
use NoreSources\SQL\Structure\PrimaryKeyTableConstraint;
use NoreSources\SQL\Structure\StructureElementInterface;
use NoreSources\SQL\Structure\TableStructure;
use NoreSources\SQL\Structure\VirtualStructureResolver;
use NoreSources\SQL\Structure\Inspector\StructureInspector;
use NoreSources\SQL\Syntax\ColumnDeclaration;
use NoreSources\SQL\Syntax\Data;
use NoreSources\SQL\Syntax\Statement\ParameterData;
use NoreSources\SQL\Syntax\Statement\ParameterDataProviderInterface;
use NoreSources\SQL\Syntax\Statement\Statement;
use NoreSources\SQL\Syntax\Statement\StatementBuilder;
use NoreSources\SQL\Syntax\Statement\StatementTokenStreamContext;
use NoreSources\SQL\Syntax\Statement\Manipulation\DeleteQuery;
use NoreSources\SQL\Syntax\Statement\Manipulation\InsertQuery;
use NoreSources\SQL\Syntax\Statement\Manipulation\UpdateQuery;
use NoreSources\SQL\Syntax\Statement\Query\SelectQuery;
use NoreSources\SQL\Syntax\Statement\Structure\DropNamespaceQuery;
use NoreSources\Test\ConnectionHelper;
use NoreSources\Test\DatasourceManager;
use NoreSources\Test\DerivedFileTestTrait;
use NoreSources\Test\SqlFormatter;
use NoreSources\Test\UnittestConnectionManagerTrait;
use NoreSources\Type\TypeConversion;
use NoreSources\Type\TypeDescription;
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

	public function log($level, $message, array $context = array())
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

	use UnittestConnectionManagerTrait;
	use DerivedFileTestTrait;

	public function __construct($name = null, array $data = [],
		$dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->initializeDerivedFileTest(__DIR__);
		$this->structures = new DatasourceManager();
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
		$settings = $this->getAvailableConnectionNames();

		if (Container::count($settings) == 0)
		{
			$this->assertTrue(true);
			return;
		}

		$this->logInit();

		foreach ($settings as $dbmsName)
		{
			$connection = $this->getConnection($dbmsName);
			$this->assertInstanceOf(ConnectionInterface::class,
				$connection, $dbmsName);

			$dbmsName = $this->getDBMSName($connection);

			$this->assertTrue($connection->isConnected(),
				$dbmsName . ' connected');

			$platform = $connection->getPlatform();

			$this->assertInstanceOf(PlatformInterface::class, $platform,
				$dbmsName);

			$this->logKeyValue($dbmsName,
				TypeDescription::getLocalName($platform));

			$this->logKeyValue('Version',
				$platform->getPlatformVersion(), 1);
			$this->logKeyValue('Compability',
				$platform->getPlatformVersion(
					PlatformInterface::VERSION_COMPATIBILITY), 1);

			$configurator = $connection->getConfigurator();
			foreach ([
				K::CONFIGURATION_KEY_CONSTRAINTS => 'Key constraints',
				K::CONFIGURATION_SUBMIT_TIMEOUT => 'Submit timeout',
				K::CONFIGURATION_TIMEZONE => 'Time zone'
			] as $key => $label)
			{
				if ($configurator->has($key))
					$this->logKeyValue($label, $configurator->get($key),
						1);
			}

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

			/**
			 *
			 * @var DropNamespaceQuery
			 */
			$drop = $platform->newStatement(DropNamespaceQuery::class);
			$this->assertInstanceOf(DropNamespaceQuery::class, $drop,
				$dbmsName . ' has DROP NAMESPACE');

			$drop->identifier('ns_unittests');
			$drop->dropFlags(
				$drop->getDropFlags() | K::DROP_EXISTS_CONDITION |
				K::DROP_CASCADE);
			$e = new Environment($connection);
			$builder = StatementBuilder::getInstance();
			$resolver = new VirtualStructureResolver();
			$data = $builder($drop, $connection->getPlatform(),
				$resolver);
			$sql = \SqlFormatter::format(\strval($data), false);
			$this->assertDerivedFile($sql, __METHOD__,
				$dbmsName . '_dropnamespace', 'sql');
			try
			{
				$e->executeStatement($data);
			}
			catch (ConnectionException $e)
			{
				$platformDropFlags = $platform->queryFeature(
					[
						K::FEATURE_DROP,
						K::FEATURE_ELEMENT_NAMESPACE,
						K::FEATURE_DROP_FLAGS
					], 0);
				if ($platformDropFlags & K::FEATURE_DROP_EXISTS_CONDITION)
				{
					$this->assertTrue(false,
						$dbmsName .
						' should accepts DROP NAMESPACE IF EXISTS.' .
						PHP_EOL . $e->getMessage());
				}
			}
		}
	}

	public function testTypeMapping()
	{
		$this->runConnectionTest(__METHOD__);
	}

	public function dbmsTypeMapping(ConnectionInterface $connection,
		$dbmsName, $method)
	{
		$platform = $connection->getPlatform();
		$context = new StatementTokenStreamContext($platform);
		$builder = new StatementBuilder();
		$tableWithPk = new TableStructure('table');
		$pk = new ColumnStructure('pk');
		$tableWithPk->addConstraint(
			new PrimaryKeyTableConstraint([
				$pk->getName()
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

			$inspector = StructureInspector::getInstance();
			$flags = $inspector->getTableColumnConstraintFlags($column);
			$this->assertEquals(
				($primary ? K::CONSTRAINT_COLUMN_PRIMARY_KEY : 0),
				($flags & K::CONSTRAINT_COLUMN_PRIMARY_KEY),
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

		$this->assertDerivedFile($content, $method, $dbmsName, 'sql');
	}

	public function testTypeSerialization()
	{
		$this->runConnectionTest(__METHOD__,
			function ($c) {
				return !($c instanceof PDOConnection);
			});
	}

	public function dbmsTypeSerialization(
		ConnectionInterface $connection, $dbmsName, $method)
	{
		$this->setTimezone($connection, 'Europe/Paris');

		$structure = $this->structures->get('types');
		$this->assertInstanceOf(StructureElementInterface::class,
			$structure);
		$tableStructure = $structure['ns_unittests']['types'];
		$this->assertInstanceOf(TableStructure::class, $tableStructure);

		$result = $this->recreateTable($connection, $tableStructure,
			$method, false);
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
					'expected' => 1.23456
				],
				'fixed_precision' => [
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
				],
				'fixed_precision' => [
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
			 * @var \NoreSources\SQL\Syntax\Statement\Manipulation\InsertQuery $q
			 */
			$q = $connection->getPlatform()->newStatement(
				InsertQuery::class);
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

	public function testParameters()
	{
		$this->runConnectionTest(__METHOD__);
	}

	private function dbmsParameters(ConnectionInterface $connection,
		$dbmsName, $method)
	{
		$platform = $connection->getPlatform();

		/**
		 *
		 * @var SelectQuery $subSelect
		 */
		$subSelect = $platform->newStatement(SelectQuery::class);

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

		$this->assertDerivedFile($subSQL, $method,
			$dbmsName . '_subquery', 'sql');

		/**
		 *
		 * @var SelectQuery $mainSelect
		 */
		$mainSelect = $platform->newStatement(SelectQuery::class);

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

		$unserializedStatement = new Statement();
		{
			$json = $mainData->jsonSerialize();
			$serialized = $mainData->serialize();
			$unserializedStatement->unserialize($serialized);
		}

		foreach ([
			'prepared' => $mainData,
			'unserialized' => $unserializedStatement
		] as $label => $data)
		{
			$this->assertEquals(K::QUERY_SELECT,
				$data->getStatementType(),
				$dbmsName . ' ' . $label . ' statement type');

			$columns = $data->getResultColumns();

			$this->assertCount(1, $columns,
				$dbmsName . ' ' . $label . ' result column count');

			$parameters = $data->getParameters();
			$this->assertCount(2, $parameters,
				$dbmsName . ' ' . $label . ' parameter count');

			$firstParameter = $parameters->get(0);
			$this->assertEquals('nsname',
				$firstParameter[ParameterData::KEY],
				$dbmsName . ' ' . $label . ' first parameter key');

			$secondparameter = $parameters->get(1);
			$this->assertEquals('param',
				$secondparameter[ParameterData::KEY],
				$dbmsName . ' ' . $label . ' second parameter key');

			$sql = \SqlFormatter::format(\strval($data), false);

			$this->assertDerivedFile($sql, $method,
				$dbmsName . '_mainquery', 'sql');
		}
	}

	public function testWildParameter()
	{
		$this->runConnectionTest(__METHOD__);
	}

	private function dbmsWildParameter(ConnectionInterface $connection,
		$dbmsName, $method)
	{
		$platform = $connection->getPlatform();

		$hasNamedParameters = $platform->queryFeature(
			K::FEATURE_NAMED_PARAMETERS, false);

		/**
		 *
		 * @var SelectQuery
		 */
		$select = $platform->newStatement(SelectQuery::class);
		$select->columns(':one[string]', ':two[string]', ':one[string]');
		$prepared = ConnectionHelper::prepareStatement($connection,
			$select);

		$sql = SqlFormatter::format(\strval($prepared), false);

		$this->assertDerivedFile($sql, $method, $dbmsName . '_query',
			'sql');

		$expected = [
			'foo',
			'bar',
			'foo'
		];

		$namedParameters = [
			'one' => 'foo',
			'two' => 'bar'
		];

		$testName = $dbmsName .
			' Query statement object with named parameters';
		try
		{
			$row = ConnectionHelper::queryFirstRow($connection,
				$prepared,
				K::RECORDSET_FETCH_INDEXED |
				K::RECORDSET_FETCH_UNSERIALIZE, $namedParameters);
		}
		catch (\Exception $e)
		{
			$this->assertTrue(false,
				$testName . PHP_EOL . $e->getMessage() . PHP_EOL .
				\strval($prepared));
		}

		$this->assertEquals($expected, $row, $testName);

		$indexedParameters = [
			'foo',
			'bar',
			'foo'
		];

		$row = ConnectionHelper::queryFirstRow($connection, $prepared,
			K::RECORDSET_FETCH_INDEXED | K::RECORDSET_FETCH_UNSERIALIZE,
			$indexedParameters);

		$this->assertEquals($expected, $row,
			$dbmsName . ' Query statement object with indexed parameters');

		$sql = \strval($prepared);

		$row = null;
		$testName = $dbmsName . ' Query raw SQL with indexed parameters' .
			PHP_EOL . $sql;

		try
		{

			$row = ConnectionHelper::queryFirstRow($connection, $sql,
				K::RECORDSET_FETCH_INDEXED |
				K::RECORDSET_FETCH_UNSERIALIZE, $indexedParameters);

			$this->assertEquals($expected, $row, $testName);
		}
		catch (\Exception $e)
		{
			/**
			 *
			 * @note We always use the :named_param syntax to render parameter for PDO driver.
			 * PDO seems to expect the "?" syntax while using indexed parameter values
			 */
			if (!($connection instanceof PDOConnection))
				$this->assertTrue(false,
					$e->getMessage() . PHP_EOL . $testName);
		}

		if ($hasNamedParameters)
		{
			$row = ConnectionHelper::queryFirstRow($connection, $sql,
				K::RECORDSET_FETCH_INDEXED |
				K::RECORDSET_FETCH_UNSERIALIZE, $namedParameters);

			$this->assertEquals($expected, $row,
				$dbmsName . ' Query raw SQL with named parameters' .
				PHP_EOL . $sql);
		}
	}

	public function testParametersTypes()
	{
		$this->runConnectionTest(__METHOD__);
	}

	private function dbmsParametersTypes(
		ConnectionInterface $connection, $dbmsName, $method)
	{
		$this->setTimezone($connection, 'Europe/Paris');
		$structure = $this->structures->get('types');
		$this->assertInstanceOf(StructureElementInterface::class,
			$structure);
		$tableStructure = $structure['ns_unittests']['types'];
		$this->assertInstanceOf(TableStructure::class, $tableStructure);

		$this->recreateTable($connection, $tableStructure, $method,
			!($connection instanceof PDOConnection));

		$platform = $connection->getPlatform();

		$hasNamedParameters = $platform->queryFeature(
			K::FEATURE_NAMED_PARAMETERS, false);

		/**
		 *
		 * @var \NoreSources\SQL\Syntax\Statement\Manipulation\InsertQuery $i
		 */
		$i = $connection->getPlatform()->newStatement(
			InsertQuery::class);
		$i->table($tableStructure);
		$i('int', ':even');
		$i('large_int', ':odd');
		$i('small_int', ':even');

		$select = $platform->newStatement(SelectQuery::class);
		$select->from($tableStructure);
		$select = ConnectionHelper::buildStatement($connection, $select,
			$tableStructure);

		$delete = ConnectionHelper::buildStatement($connection,
			new DeleteQuery($tableStructure), $tableStructure);

		$insert = ConnectionHelper::prepareStatement($connection, $i,
			$tableStructure);
		$parameterCount = $insert->getParameters()->count();
		$distinctParameterCount = $insert->getParameters()->getDistinctParameterCount();
		$parameterDesc = json_encode(
			$insert->getParameters()->getArrayCopy(), JSON_PRETTY_PRINT);

		$this->assertEquals(3, $parameterCount,
			$dbmsName . ' INSERT parameter count' . PHP_EOL .
			$parameterDesc);
		$this->assertEquals(2, $distinctParameterCount,
			$dbmsName . ' INSERT distinct parameter count' . PHP_EOL .
			$parameterDesc);

		$sql = \SqlFormatter::format(\strval($insert), false);
		$this->assertDerivedFile($sql, $method, $dbmsName . '_insert',
			'sql');

		// Test parameter binding with raw SQL
		{
			$sql = \strval($insert);

			$rawPrepared = $connection->prepareStatement($sql);
			$this->assertInstanceOf(
				ParameterDataProviderInterface::class, $rawPrepared,
				$dbmsName . ' prepare raw SQL');

			$rawDistinctCount = $rawPrepared->getParameters()->getDistinctParameterCount();
			$rawCount = $rawPrepared->getParameters()->count();
			$rawDesc = \json_encode($rawPrepared->getParameters(),
				JSON_PRETTY_PRINT);

			if (!($connection instanceof PDOConnection))
			{
				if ($hasNamedParameters)
					$this->assertEquals($distinctParameterCount,
						$rawDistinctCount,
						$dbmsName .
						' raw prepared distinch parameter count' .
						PHP_EOL . $sql . PHP_EOL . $rawDesc);
				else
					$this->assertEquals($parameterCount, $rawCount,
						$dbmsName . ' raw prepared parameter count' .
						PHP_EOL . $sql . PHP_EOL . $rawDesc);
			}
		}

		$tests = [
			'simple test' => [
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
			$this->queryTest($connection, $test['expected'],
				[
					'insert' => [
						$insert,
						$test['parameters']
					],
					'select' => $select,
					'cleanup' => $delete
				]);
		}

		$insert = $platform->newStatement(InsertQuery::class);
		$insert->into($tableStructure);
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
			$this->queryTest($connection, $test['expected'],
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
			$this->queryTest($connection, $test['expected'],
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
		$settings = $this->getAvailableConnectionNames();
		if (\count($settings) == 0)
		{
			$this->assertTrue(true, 'Skip');
			return;
		}
		foreach ($settings as $dbmsName)
		{
			$connection = $this->getConnection($dbmsName);

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
			UpdateQuery::class);
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

			$text = $this->getRowValue($connection, $select, 'text',
				[
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

			$text = $this->getRowValue($connection, $select, 'text',
				[
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
		$this->runConnectionTest(__METHOD__);
	}

	private function dbmsEmployeesTable(ConnectionInterface $connection,
		$dbmsName, $method)
	{
		$structure = $this->structures->get('Company');
		$tableStructure = $structure['ns_unittests']['Employees'];
		$this->recreateTable($connection, $tableStructure, $method,
			!($connection instanceof PDOConnection));

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
			$this->assertDerivedFile($sql, $method,
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
			SelectQuery::class);

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
			SelectQuery::class);
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
				InsertQuery::class);
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
			$this->assertTrue($result->getResultColumns()
				->has($index),
				$dbmsName . ' column #' . $index . ' exists');

			$this->assertTrue($result->getResultColumns()
				->has($name),
				$dbmsName . ' column "' . $name . '" exists');

			$byIndex = $result->getResultColumns()->get($index);
			$this->assertEquals($name, $byIndex->getName(),
				$dbmsName . ' Recordset result column #' . $index);

			$byName = $result->getResultColumns()->get($name);
			$this->assertEquals($name, $byName->getName(),
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
				K::RECORDSET_FETCH_UNSERIALIZE);

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
					K::RECORDSET_FETCH_UNSERIALIZE);
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

	public function testExplorer()
	{
		$this->runConnectionTest(__METHOD__);
	}

	private function dbmsExplorer(ConnectionInterface $connection,
		$dbmsName, $method)
	{
		$this->setTimezone($connection, 'Europe/Paris');
		if (!($connection instanceof StructureExplorerProviderInterface))
			return;

		$referenceStructure = $this->structures->get('Company');
		$referenceEmployeesStructure = $referenceStructure['ns_unittests']['Employees'];

		$nsName = 'ns_unittests';
		$structure = $this->structures->get('Company');
		$tableStructure = $structure[$nsName]['Employees'];
		$this->recreateTable($connection, $tableStructure, $method,
			false);

		$tableStructure = $structure[$nsName]['Hierarchy'];
		$this->recreateTable($connection, $tableStructure, $method,
			false);

		$tableStructure = $structure[$nsName]['types'];
		$this->recreateTable($connection, $tableStructure, $method,
			!($connection instanceof PDOConnection));

		$explorer = $connection->getStructureExplorer();

		$namespaces = $explorer->getNamespaceNames();

		$this->assertContains($nsName, $namespaces,
			$dbmsName . '. Find ' . $nsName . ' namespace in ' .
			Container::implodeValues($namespaces, '', ', '));

		$tables = $explorer->getTableNames('ns_unittests');

		$this->assertContains('Employees', $tables, $dbmsName);

		$employeesColumns = $explorer->getTableColumnNames(
			'ns_unittests.Employees');

		$this->assertContains('gender', $employeesColumns);

		// Primary key ----------------------

		$employeesPrimaryKey = $explorer->getTablePrimaryKeyConstraint(
			'ns_unittests.Employees');

		$this->assertInstanceOf(PrimaryKeyTableConstraint::class,
			$employeesPrimaryKey, $dbmsName . ' Employees primary key');

		$this->assertCount(1, $employeesPrimaryKey->getColumns(),
			'Employees primary key column count');

		/**
		 *
		 * @note MySQL primary key name is always PIRMARY
		 */
		if (!$this->isPlatform($connection->getPlatform(),
			MySQLPlatform::class))
		{
			$this->assertEquals('pk_id', $employeesPrimaryKey->getName(),
				$dbmsName . ' Employees primary key name');
		}

		$this->assertContains('id', $employeesPrimaryKey->getColumns(),
			$dbmsName . ' Employees primary key column');

		// Indexes ----------------------

		if (false)
		{
			$employeesIndexNames = $explorer->getTableIndexNames(
				'ns_unittests.Employees');

			$this->assertCount(\count($employeesIndexNames),
				$employeesIndexes, $dbmsName . ' Number of indexes');
		}

		// Foreign key ----------------------

		$hierarchyForeignKeys = $explorer->getTableForeignKeyConstraints(
			'ns_unittests.Hierarchy');

		$this->assertCount(2, $hierarchyForeignKeys,
			'Hierarchy foreign keys');

		$hierarchy_managerId_foreignkey = null;
		foreach ($hierarchyForeignKeys as $key)
		{
			if ($key->getName() == 'hierarchy_managerId_foreignkey')
				$hierarchy_managerId_foreignkey = $key;
		}

		$this->assertInstanceOf(ForeignKeyTableConstraint::class,
			$hierarchy_managerId_foreignkey,
			$dbmsName .
			' foreign key named hierarchy_managerId_foreignkey');

		$this->assertEquals(K::FOREIGN_KEY_ACTION_CASCADE,
			$hierarchyForeignKeys[1]->getEvents()
				->get(K::EVENT_UPDATE), 'Foreign key ON UPDATE action');

		/**
		 *
		 * @todo look for indexes instread of constraints
		 */
		if (false)
		{
			$employeesIndexes = $explorer->getTableIndexes(
				'ns_unittests.Employees');

			$employeesNAmeIndex = Container::firstValue(
				Container::filter($employeesIndexes,
					function ($k, $v) {
						return $v->getName() == 'index_employees_name';
					}));

			$this->assertInstanceOf(IndexTableConstraint::class,
				$employeesNAmeIndex);

			$this->assertCount(1, $employeesNAmeIndex->getColumns(),
				$dbmsName . ' Index column count');

			/**
			 *
			 * @var IndexTableConstraint $employeesNAmeIndex
			 */

			$this->assertEquals(0,
				$employeesNAmeIndex->getIndexFlags() & K::INDEX_UNIQUE,
				$dbmsName . ' Index is not unique');

			$this->assertContains('name',
				$employeesNAmeIndex->getColumns(),
				$dbmsName . ' Index column name');
		}
		// Columns ----------------------

		/**
		 *
		 * @var ColumnDescriptionInterface $typesTimestamp
		 */
		$typesTimestamp = $explorer->getTableColumn(
			'ns_unittests.types', 'timestamp');

		$this->assertInstanceOf(ColumnDescriptionInterface::class,
			$typesTimestamp);

		$this->assertTrue($typesTimestamp->has(K::COLUMN_DEFAULT_VALUE),
			'Timestamp has default value');

		$dfltValue = $typesTimestamp->get(K::COLUMN_DEFAULT_VALUE);
		$this->assertInstanceOf(Data::class, $dfltValue);

		$typesInt = $explorer->getTableColumn('ns_unittests.types',
			'int');
		$typesIntFlags = $typesInt->get(K::COLUMN_FLAGS);

		$this->assertTrue(
			($typesIntFlags & K::COLUMN_FLAG_AUTO_INCREMENT) ==
			K::COLUMN_FLAG_AUTO_INCREMENT,
			$dbmsName . ' types.int is  Auto increament');
	}

	public function testConfigurator()
	{
		$this->runConnectionTest(__METHOD__);
	}

	private function dbmsConfigurator(ConnectionInterface $connection,
		$dbmsName, $method)
	{
		if (!($connection instanceof ConfiguratorProviderInterface))
		{
			$this->assertFalse(
				($connection instanceof ConfiguratorProviderInterface),
				$dbmsName . ' is not configurable at all');
		}

		$configurator = $connection->getConfigurator();
		$this->assertInstanceOf(ConfiguratorInterface::class,
			$configurator, $dbmsName . ' Configurator class');

		$tests = [
			K::CONFIGURATION_KEY_CONSTRAINTS => [
				'disabled' => false,
				'enabled' => true
			],
			K::CONFIGURATION_SUBMIT_TIMEOUT => [
				'3s value' => 3000
			]
		];

		$expectedTypes = [
			K::CONFIGURATION_KEY_CONSTRAINTS => 'boolean',
			K::CONFIGURATION_SUBMIT_TIMEOUT => 'integer',
			K::CONFIGURATION_TIMEZONE => \DateTimeZone::class
		];

		foreach ($tests as $key => $values)
		{
			if (!$configurator->has($key))
				continue;

			$current = $configurator->get($key);

			if (($expectedType = Container::keyValue($expectedTypes,
				$key)))
			{
				$this->assertEquals($expectedType,
					TypeDescription::getName($current),
					$dbmsName . ' ' . $key . ' expected value type');
			}

			foreach ($values as $label => $value)
			{
				$configurator[$key] = $value;
				$this->assertEquals($value, $configurator->get($key),
					$dbmsName . ' ' . $key . ' ' . $label);
			}
		}
	}

	public function testMediaTypes()
	{
		$this->runConnectionTest(__METHOD__);
	}

	private function dbmsMediaTypes(ConnectionInterface $connection,
		$dbmsName, $method)
	{
		$platform = $connection->getPlatform();

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
				MediaType::createFromString('application/json'));

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

	/**
	 *
	 * @var DatasourceManager
	 */
	private $structures;
}
