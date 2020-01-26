<?php
namespace NoreSources\SQL;

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\ConnectionHelper;
use NoreSources\SQL\DBMS\PostgreSQL\PostgreSQLStatementBuilder;
use NoreSources\SQL\Statement\CreateTableQuery;

final class PostgreSQLTest extends \PHPUnit\Framework\TestCase
{

	public function __construct($name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->connection = null;
		$this->derivedFileManager = new DerivedFileManager();
		$this->datasources = new DatasourceManager();
		$this->createdTables = new \ArrayObject();
	}

	public function testBuilder()
	{
		$structure = $this->datasources->get('Company');
		$tableStructure = $structure['ns_unittests']['Tasks'];
		$connection = ConnectionHelper::createConnection(
			[
				K::CONNECTION_PARAMETER_TYPE => \NoreSources\SQL\DBMS\PostgreSQL\PostgreSQLConnection::class
			]);
		$builder = $connection->getStatementBuilder();
		$this->assertInstanceOf(PostgreSQLStatementBuilder::class, $builder);

		$s = new CreateTableQuery($tableStructure);
		$sql = ConnectionHelper::getStatementSQL($connection, $s, $tableStructure);

		//var_dump($sql);
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