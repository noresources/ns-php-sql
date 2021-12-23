<?php
namespace NoreSources\SQL;

use NoreSources\Container\Container;
use NoreSources\SQL\DBMS\ConnectionException;
use NoreSources\SQL\DBMS\ConnectionInterface;
use NoreSources\SQL\DBMS\MySQL\MySQLConnection;
use NoreSources\SQL\DBMS\MySQL\MySQLStructureExplorer;
use NoreSources\SQL\DBMS\MySQL\MySQLTypeRegistry;
use NoreSources\SQL\DBMS\PostgreSQL\PostgreSQLConstants as K;
use NoreSources\SQL\Structure\Identifier;
use NoreSources\SQL\Syntax\Statement\Structure\CreateTableQuery;
use NoreSources\Test\ConnectionHelper;
use NoreSources\Test\DatasourceManager;

final class MySQLTest extends \PHPUnit\Framework\TestCase
{

	public function testMySQLiType()
	{
		$registry = new MySQLTypeRegistry();

		$tests = [
			'timestamp' => 'timestamp',
			MYSQLI_TYPE_TIMESTAMP => 'timestamp'
		];

		$mysqliTypeConstants = Container::filter(
			get_defined_constants(),
			function ($k, $v) use ($registry) {
				if (\strpos($k, 'MYSQLI_TYPE_') !== 0)
					return false;
				$this->assertTrue($registry->has($v),
					'Type ID ' . $k . ' exists');
			});

		foreach ($tests as $id => $name)
		{
			$this->assertTrue($registry->has($id),
				'Type ID ' . $id . ' exists');
			$type = $registry->get($id);
			$this->assertEquals($name, $type->getTypeName(),
				'Type ' . $id . ' name');
		}
	}

	public function __construct($name = null, array $data = [],
		$dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->datasources = new DatasourceManager();
	}

	public function testInvalidConnection()
	{
		if (!$this->prerequisites())
			return;
		$this->expectException(\RuntimeException::class);
		$env = new Environment(
			[
				K::CONNECTION_TYPE => MySQLConnection::class,
				K::CONNECTION_SOURCE => 'void.null.twisting-neither.shadow',
				K::CONNECTION_PORT => 0,
				K::CONNECTION_USER => 'Xul',
				K::CONNECTION_PASSWORD => 'keymaster.and.gatekeeper'
			]);
	}

	public function testExplorer()
	{
		if (!$this->prerequisites())
			return;
		$connection = $this->getConnection();
		if (!($connection instanceof ConnectionInterface))
		{
			$this->assertTrue(true);
			return;
		}

		$referenceStructure = $this->datasources->get('Company');
		$namespaceStructure = $referenceStructure['ns_unittests'];
		$employeesStructure = $namespaceStructure['Employees'];
		$hierarchyStructure = $namespaceStructure['Hierarchy'];
		try
		{
			$this->datasources->createTable($this, $connection,
				$employeesStructure, false, CreateTableQuery::REPLACE);
		}
		catch (ConnectionException $e)
		{}
		try
		{
			$this->datasources->createTable($this, $connection,
				$hierarchyStructure, false, CreateTableQuery::REPLACE);
		}
		catch (ConnectionException $e)
		{}

		$explorer = new MySQLStructureExplorer($connection);

		$namespaces = $explorer->getNamespaceNames();

		$this->assertContains('ns_unittests', $namespaces);

		if (!Container::valueExists($namespaces, 'ns_unittests'))
			return;

		$employeesTableIdentifier = Identifier::make(
			'ns_unittests.Employees');

		$primaryKey = $explorer->getTablePrimaryKeyConstraint(
			$employeesTableIdentifier);

		$fk = $explorer->getTableForeignKeyConstraints(
			$employeesTableIdentifier);

		$c = $explorer->getTableColumnNames($employeesTableIdentifier);
	}

	private function prerequisites()
	{
		if (!MySQLConnection::acceptConnection())
		{
			$this->assertFalse(false);
			return false;
		}

		return true;
	}

	protected function getConnection()
	{
		if ($this->connection instanceof MySQLConnection)
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
	 * @var MySQLConnection
	 */
	private $connection;

	/**
	 *
	 * @var DatasourceManager
	 */
	private $datasources;
}
