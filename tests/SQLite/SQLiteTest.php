<?php

namespace NoreSources\SQL;

use NoreSources\SQL\SQLite as SQLite;
use PHPUnit\Framework\TestCase;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\ExpressionEvaluator as X;

final class SQLiteTest extends TestCase
{

	public function __construct()
	{
		parent::__construct();
		$this->connection = null;
		$this->derivedFileManager = new DerivedFileManager();
		$this->datasources = new DatasourceManager();
		$this->createdTables = new \ArrayObject();
	}

	public function testParametersEmployees()
	{
		$structure = $this->datasources->get('Company');
		$tableStructure = $structure['ns_unittests']['Employees'];
		$this->assertInstanceOf(TableStructure::class, $tableStructure);

		$this->assertTrue($this->createDatabase(), 'Create SQLite file');

		$this->derivedFileManager->setPersistent($this->sqliteFile, true);

		$this->assertTrue($this->createTable($tableStructure), 'Create table ' .
			$tableStructure->getPath());

		//$this->assertTrue(false, 'Abort');

		$this->derivedFileManager->setPersistent($this->sqliteFile, false);
	}

	private function createTable(TableStructure $tableStructure)
	{
		$path = $tableStructure->getPath();
		if ($this->createdTables->offsetExists($path))
			return true;

		$q = new CreateTableQuery($tableStructure);
		$statement = ConnectionHelper::prepareStatement($this->connection, $q);
		
		$this->assertInstanceOf(PreparedStatement::class, $statement);

		$this->connection->executeStatement($statement);

		return true;
	}

	private function createDatabase()
	{
		if ($this->connection instanceof Connection)
			return true;

		$this->sqliteFile = $this->derivedFileManager->registerDerivedFile('SQLite', __METHOD__, 'db', 'sqlite');
			
		$this->connection = ConnectionHelper::createConnection([
				K::CONNECTION_PARAMETER_CREATE => true,
				K::CONNECTION_PARAMETER_SOURCE => [
						'ns_unittests' => $this->sqliteFile
				],
				K::CONNECTION_PARAMETER_TYPE => SQLite\Connection::class
		]);
		
		$this->assertInstanceOf(SQLite\Connection::class, $this->connection, 'Create connection');
		
		return true;
	}
	
	private $sqliteFile;
	
	/**
	 * @var DatasourceManager
	 */
	private $datasources;

	/**
	 * @var DerivedFileManager
	 */
	private $derivedFileManager;

	/**
	 * @var \ArrayObject
	 */
	private $createdTables;

	/**
	 * @var Connection
	 */
	private $connection;
}