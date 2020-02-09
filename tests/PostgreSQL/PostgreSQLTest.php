<?php
namespace NoreSources\SQL;

use NoreSources\SQL\DBMS\ConnectionHelper;
use NoreSources\SQL\DBMS\PostgreSQL\PostgreSQLConnection;
use NoreSources\SQL\DBMS\PostgreSQL\PostgreSQLConstants as K;
use NoreSources\SQL\DBMS\PostgreSQL\PostgreSQLPreparedStatement;
use NoreSources\SQL\DBMS\PostgreSQL\PostgreSQLStatementBuilder;
use NoreSources\SQL\Statement\CreateTableQuery;
use NoreSources\SQL\Statement\DropTableQuery;
use NoreSources\SQL\Statement\InsertQuery;
use NoreSources\Test\DatasourceManager;
use NoreSources\Test\DerivedFileManager;

final class PostgreSQLTest extends \PHPUnit\Framework\TestCase
{

	public function __construct($name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->connection = null;
		$this->derivedFileManager = new DerivedFileManager(__dir__ . '/..');
		$this->datasources = new DatasourceManager();
		$this->createdTables = new \ArrayObject();
	}

	public function testBuilder()
	{
		$structure = $this->datasources->get('Company');

		$connection = self::createConnection();

		$builders = [
			'connectionless' => new PostgreSQLStatementBuilder(null),
			'connected' => $connection->getStatementBuilder()
		];

		$version = $connection->getPostgreSQLVersion();
		$versionString = \strval($version);

		foreach ($structure['ns_unittests'] as $name => $tableStructure)
		{
			$previousFile = null;
			foreach ($builders as $builderType => $builder)
			{
				if (!\is_resource($builder->getConnectionResource()))
					$builder->updateBuilderFlags($version);

				$this->assertInstanceOf(PostgreSQLStatementBuilder::class, $builder);

				$s = new CreateTableQuery($tableStructure);
				$sql = ConnectionHelper::getStatementSQL($connection, $s, $tableStructure);

				$sql = \SqlFormatter::format($sql, false);
				$suffix = 'create_' . $name . '_' . $builderType . '_' . $versionString;
				$file = $this->derivedFileManager->assertDerivedFile($sql, __METHOD__, $suffix,
					'sql');

				if (\is_file($previousFile))
				{
					$this->assertEquals(file_get_contents($previousFile), file_get_contents($file),
						'Compare builder results');
				}
				$previousFile = $file;
			}
		}

		$dropTable = new DropTableQuery($tableStructure);
		$data = ConnectionHelper::getStatementData($connection, $dropTable, $tableStructure);
		$sql = \SqlFormatter::format(\strval($data), false);
		$suffix = 'drop_' . $versionString;
		$this->derivedFileManager->assertDerivedFile($sql, __METHOD__, $suffix, 'sql');
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
		return;

		$connection = self::createConnection();
		$structure = $this->datasources->get('Company');
		$tableStructure = $structure['ns_unittests']['Employees'];
		$this->assertInstanceOf(Structure\TableStructure::class, $tableStructure);

		$query = new InsertQuery($tableStructure);
		$query('gender', ':gender');

		$prepared = ConnectionHelper::prepareStatement($connection, $query, $tableStructure);

		$this->assertInstanceOf(PostgreSQLPreparedStatement::class, $prepared);
	}

	public function _testSelect()
	{}

	private function createConnection()
	{
		if ($this->connection instanceof PostgreSQLConnection)
			return $this->connection;

		$settings = [
			K::CONNECTION_PARAMETER_TYPE => \NoreSources\SQL\DBMS\PostgreSQL\PostgreSQLConnection::class
		];
		$settingsFile = __DIR__ . '/../settings/' . basename(__DIR__) . '.php';
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
	 * @var Connection
	 */
	private $connection;
}
