<?php
namespace NoreSources\SQL;

use NoreSources\SemanticVersion;
use NoreSources\SQL\DBMS\ConnectionInterface;
use NoreSources\SQL\DBMS\TypeInterface;
use NoreSources\SQL\DBMS\TypeRegistry;
use NoreSources\SQL\DBMS\PostgreSQL\PostgreSQLConnection;
use NoreSources\SQL\DBMS\PostgreSQL\PostgreSQLConstants as K;
use NoreSources\SQL\DBMS\PostgreSQL\PostgreSQLPreparedStatement;
use NoreSources\SQL\DBMS\PostgreSQL\PostgreSQLTypeRegistry;
use NoreSources\SQL\Statement\Statement;
use NoreSources\SQL\Statement\Structure\CreateIndexQuery;
use NoreSources\SQL\Statement\Structure\CreateTableQuery;
use NoreSources\SQL\Statement\Structure\DropIndexQuery;
use NoreSources\SQL\Statement\Structure\DropTableQuery;
use NoreSources\SQL\Structure\IndexStructure;
use NoreSources\SQL\Structure\TableStructure;
use NoreSources\Test\ConnectionHelper;
use NoreSources\Test\DatasourceManager;
use NoreSources\Test\DerivedFileManager;

final class PostgreSQLTest extends \PHPUnit\Framework\TestCase
{

	public function __construct($name = null, array $data = [],
		$dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->connection = null;
		$this->derivedFileManager = new DerivedFileManager(
			__dir__ . '/..');
		$this->datasources = new DatasourceManager();
		$this->createdTables = new \ArrayObject();
	}

	public function testTypes()
	{
		/**
		 *
		 * @var TypeRegistry $types
		 */
		$types = PostgreSQLTypeRegistry::getInstance();
		$smallint = $types->get('smallint');
		$this->assertInstanceOf(TypeInterface::class, $smallint);
		$this->assertEquals('smallint', $smallint->get(K::TYPE_NAME),
			'smallint type name');

		$bigint = $types->get('bigint');
		$this->assertInstanceOf(TypeInterface::class, $bigint);
		$this->assertEquals('bigint', $bigint->get(K::TYPE_NAME),
			'bigint type name');

		$diff = TypeRegistry::compareTypeLength($smallint, $bigint);
		$this->assertLessThan(0, $diff,
			'smallint length < biging length');
	}

	public function testBuilder()
	{
		$structure = $this->datasources->get('Company');

		/**
		 *
		 * @var PostgreSQLConnection $connection
		 */
		$connection = self::createConnection();

		$version = $connection->getPlatform()->getPlatformVersion(
			K::PLATFORM_VERSION_COMPATIBILITY);
		$versionString = $version->slice(SemanticVersion::MAJOR,
			SemanticVersion::MINOR);

		foreach ($structure['ns_unittests'] as $name => $elementStructure)
		{
			$s = null;
			if ($elementStructure instanceof TableStructure)
			{
				$s = $connection->getPlatform()->newStatement(
					K::QUERY_CREATE_TABLE);
				if ($s instanceof CreateTableQuery)
					$s->table($elementStructure);
			}
			elseif ($elementStructure instanceof IndexStructure)
			{
				$s = $connection->getPlatform()->newStatement(
					K::QUERY_CREATE_INDEX);
				if ($s instanceof CreateIndexQuery)
					$s->setFromIndexStructure($elementStructure);
			}
			else
				continue;

			$this->assertInstanceOf(Statement::class, $s,
				'Valid CREATE query');

			$sql = ConnectionHelper::buildStatement($connection, $s,
				$elementStructure);
			$sql = \strval($sql);

			$sql = \SqlFormatter::format($sql, false);
			$suffix = 'create_' . $name . '_' . $versionString;
			$file = $this->derivedFileManager->assertDerivedFile($sql,
				__METHOD__, $suffix, 'sql');

			$drop = null;
			if ($elementStructure instanceof TableStructure)
				$drop = new DropTableQuery($elementStructure);
			elseif ($elementStructure instanceof IndexStructure)
				$drop = new DropIndexQuery($elementStructure);
			$data = ConnectionHelper::buildStatement($connection, $drop,
				$elementStructure);
			$sql = \SqlFormatter::format(\strval($data), false);
			$suffix = 'drop_' . $name . '_' . $versionString;
			$this->derivedFileManager->assertDerivedFile($sql,
				__METHOD__, $suffix, 'sql');
		}
	}

	public function testTypeMapping()
	{
		$structure = $this->datasources->get('types');

		$tests = [
			'unknown' => [
				'expected' => 'text'
			]
		];
	}

	public function testParameters()
	{
		$this->assertEquals(true, true);

		$connection = self::createConnection();
		$structure = $this->datasources->get('Company');
		$tableStructure = $structure['ns_unittests']['Employees'];
		$this->assertInstanceOf(Structure\TableStructure::class,
			$tableStructure);

		/**
		 *
		 * @var \NoreSources\SQL\Statement\Manipulation\InsertQuery $query
		 */
		$query = $connection->getPlatform()->newStatement(
			K::QUERY_INSERT);
		$query->table($tableStructure);
		$query('gender', ':gender');

		$prepared = ConnectionHelper::prepareStatement($connection,
			$query, $tableStructure);

		$this->assertInstanceOf(PostgreSQLPreparedStatement::class,
			$prepared);
	}

	public function _testSelect()
	{}

	public function testInvalidConnection()
	{
		$this->expectException(\RuntimeException::class);
		$env = new Environment(
			[
				K::CONNECTION_TYPE => PostgreSQLConnection::class,
				K::CONNECTION_SOURCE => 'void.null.twisting-neither.shadow',
				K::CONNECTION_PORT => 0,
				K::CONNECTION_USER => 'Xul',
				K::CONNECTION_PASSWORD => 'keymaster.and.cerberus'
			]);
	}

	/**
	 *
	 * @return \NoreSources\SQL\DBMS\ConnectionInterface
	 */
	private function createConnection()
	{
		if ($this->connection instanceof PostgreSQLConnection)
			return $this->connection;

		$settings = [
			K::CONNECTION_TYPE => \NoreSources\SQL\DBMS\PostgreSQL\PostgreSQLConnection::class
		];
		$settingsFile = __DIR__ . '/../settings/' . basename(__DIR__) .
			'.php';
		if (\file_exists($settingsFile))
			$settings = require ($settingsFile);

		return ConnectionHelper::createConnection($settings);
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
	 * @var ConnectionInterface
	 */
	private $connection;
}
