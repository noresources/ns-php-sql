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
use NoreSources\SQL\Structure\TableStructure;
use NoreSources\SQL\Syntax\Statement\TokenizableStatementInterface;
use NoreSources\SQL\Syntax\Statement\Manipulation\InsertQuery;
use NoreSources\SQL\Syntax\Statement\Structure\CreateTableQuery;
use NoreSources\SQL\Syntax\Statement\Structure\DropTableQuery;
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
		if (!$this->prerequisites())
			return;
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
		if (!$this->prerequisites())
			return;
		$structure = $this->datasources->get('Company');

		/**
		 *
		 * @var PostgreSQLConnection $connection
		 */
		$connection = self::createConnection();
		$environment = new Environment($connection);

		if ($connection === NULL)
		{
			$this->assertTrue(true, 'Not available');
			return;
		}

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
					CreateTableQuery::class);
				$this->assertInstanceOf(CreateTableQuery::class, $s);
				$s->createFlags(K::CREATE_EXISTS_CONDITION);
				$s->table($elementStructure);
			}
			else
				continue;

			$this->assertInstanceOf(
				TokenizableStatementInterface::class, $s,
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

			$drop->flags($drop->getFlags() | K::DROP_CASCADE);
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
		if (!$this->prerequisites())
			return;
		$structure = $this->datasources->get('types');

		$tests = [
			'unknown' => [
				'expected' => 'text'
			]
		];
	}

	public function testParameters()
	{
		if (!$this->prerequisites())
			return;
		$this->assertEquals(true, true);

		$connection = self::createConnection();
		if ($connection === NULL)
			return;
		$structure = $this->datasources->get('Company');

		$environment = new Environment($connection, $structure);

		$tableStructure = $structure['ns_unittests']['Employees'];
		$this->assertInstanceOf(Structure\TableStructure::class,
			$tableStructure);

		$this->datasources->createTable($this, $connection,
			$tableStructure);

		/**
		 *
		 * @var \NoreSources\SQL\Syntax\Statement\Manipulation\InsertQuery $query
		 */
		$query = $environment->getPlatform()->newStatement(
			InsertQuery::class);
		$query->table($tableStructure);
		$query('gender', ':gender');

		$prepared = ConnectionHelper::prepareStatement($connection,
			$query, $tableStructure);

		$this->assertInstanceOf(PostgreSQLPreparedStatement::class,
			$prepared);
	}

	public function testExplorer()
	{
		if (!$this->prerequisites())
			return;
		$this->assertTrue(true);

		$connection = self::createConnection();
		if ($connection === NULL)
			return;

		$explorer = $connection->getStructureExplorer();

		$namespaces = $explorer->getNamespaceNames();

		$this->assertContains('public', $namespaces, 'Namespace names');
		$this->assertContains('ns_unittests', $namespaces,
			'Namespace names');

		$utTables = $explorer->getTableNames('ns_unittests');
	}

	public function _testSelect()
	{}

	public function testInvalidConnection()
	{
		if (!$this->prerequisites())
			return;
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

	private function prerequisites()
	{
		if (!PostgreSQLConnection::acceptConnection())
		{
			$this->assertFalse(false);
			return false;
		}

		return true;
	}

	/**
	 *
	 * @return PostgreSQLConnection
	 */
	private function createConnection()
	{
		if ($this->connection instanceof PostgreSQLConnection)
			return $this->connection;

		$settingsFile = __DIR__ . '/../settings/' . basename(__DIR__) .
			'.php';
		if (!\file_exists($settingsFile))
			return NULL;

		$settings = require ($settingsFile);

		$this->connection = ConnectionHelper::createConnection(
			$settings);
		return $this->connection;
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
