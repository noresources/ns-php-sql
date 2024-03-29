<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL;

use NoreSources\SemanticVersion;
use NoreSources\Container\Container;
use NoreSources\SQL\DBMS\ConnectionException;
use NoreSources\SQL\DBMS\ConnectionInterface;
use NoreSources\SQL\DBMS\TypeInterface;
use NoreSources\SQL\DBMS\TypeRegistry;
use NoreSources\SQL\DBMS\Explorer\StructureExplorerInterface;
use NoreSources\SQL\DBMS\PostgreSQL\PostgreSQLConnection;
use NoreSources\SQL\DBMS\PostgreSQL\PostgreSQLConstants as K;
use NoreSources\SQL\DBMS\PostgreSQL\PostgreSQLPreparedStatement;
use NoreSources\SQL\DBMS\PostgreSQL\PostgreSQLTypeRegistry;
use NoreSources\SQL\Structure\ArrayColumnDescription;
use NoreSources\SQL\Structure\TableStructure;
use NoreSources\SQL\Structure\Comparer\StructureComparer;
use NoreSources\SQL\Syntax\Statement\TokenizableStatementInterface;
use NoreSources\SQL\Syntax\Statement\Manipulation\InsertQuery;
use NoreSources\SQL\Syntax\Statement\Structure\CreateTableQuery;
use NoreSources\SQL\Syntax\Statement\Structure\DropTableQuery;
use NoreSources\Test\ConnectionHelper;
use NoreSources\Test\DatasourceManager;
use NoreSources\Test\DerivedFileTestTrait;
use NoreSources\Test\SqlFormatter;
use NoreSources\Test\UnittestConnectionManagerTrait;
use NoreSources\Test\UnittestStructureComparerTrait;
use PHPUnit\Framework\TestCase;

final class PostgreSQLTest extends TestCase
{

	use DerivedFileTestTrait;
	use UnittestConnectionManagerTrait;
	use UnittestStructureComparerTrait;

	const TEST_NAMESPACE = 'ns_unittests';

	public function __construct($name = null, array $data = [],
		$dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->connection = null;
		$this->initializeDerivedFileTest(__dir__ . '/..');
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

		foreach ($structure[self::TEST_NAMESPACE] as $name => $elementStructure)
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

			$sql = SqlFormatter::format($sql, false);
			$suffix = 'create_' . $name . '_' . $versionString;
			$file = $this->assertDerivedFile($sql, __METHOD__, $suffix,
				'sql');

			$drop = null;
			if ($elementStructure instanceof TableStructure)
				$drop = new DropTableQuery($elementStructure);

			$drop->dropFlags($drop->getDropFlags() | K::DROP_CASCADE);
			$data = ConnectionHelper::buildStatement($connection, $drop,
				$elementStructure);
			$sql = SqlFormatter::format(\strval($data), false);
			$suffix = 'drop_' . $name . '_' . $versionString;
			$this->assertDerivedFile($sql, __METHOD__, $suffix, 'sql');
		}
	}

	public function testTypeMapping()
	{
		if (!$this->prerequisites())
			return;
		$structure = $this->datasources->get('types');

		$tests = [
			'float with precision scale' => [
				'expected' => 'numeric',
				'column' => [
					K::COLUMN_DATA_TYPE => K::DATATYPE_REAL,
					K::COLUMN_FRACTION_SCALE => 2
				]
			]
		];

		/**
		 *
		 * @var PostgreSQLConnection $connection
		 */
		$connection = self::createConnection();
		if (!$connection)
			return;
		$registry = $connection->getPlatform()->getTypeRegistry();

		foreach ($tests as $label => $test)
		{
			$expected = $test['expected'];
			$description = $test['column'];
			$column = new ArrayColumnDescription($description);

			$type = null;
			$error = null;
			try
			{
				$list = $registry->matchDescription($column);
				$type = Container::firstValue($list);
			}
			catch (\Exception $e)
			{
				$error = $e;
				var_dump($e->getMessage());
			}

			if ($type instanceof TypeInterface)
				$this->assertEquals($expected, $type->getTypeName(),
					$label);
			else
				$this->assertInstanceOf($expected, $error, $label);
		}
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

		$tableStructure = $structure[self::TEST_NAMESPACE]['Employees'];
		$this->assertInstanceOf(Structure\TableStructure::class,
			$tableStructure);

		$this->datasources->createTable($this, $connection,
			$tableStructure);

		/** @var InsertQuery $query */
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
			return $this->assertTrue(true, 'Prerequisites');

		$connection = null;
		try
		{
			$connection = $this->getConnection('PostgreSQL');
		}
		catch (ConnectionException $e)
		{
			return $this->assertTrue(true, 'Connection not available');
		}

		$comparer = StructureComparer::getInstance();
		$structure = $this->datasources->get('Company');
		$ns = $structure[self::TEST_NAMESPACE];

		$this->recreateTable($connection, $ns['Employees'], __METHOD__);
		$this->recreateTable($connection, $ns['Hierarchy'], __METHOD__);

		/** @var StructureExplorerInterface $explorer */
		$explorer = $connection->getStructureExplorer();

		$namespaces = $explorer->getNamespaceNames();

		$this->assertContains('public', $namespaces, 'Namespace names');
		$this->assertContains(self::TEST_NAMESPACE, $namespaces,
			'Namespace names');

		$tableNames = $explorer->getTableNames(self::TEST_NAMESPACE);

		$exploredStructure = $explorer->getStructure();
		foreach ([
			'Employees',
			'Hierarchy'
		] as $name)
		{
			$this->assertContains($name, $tableNames,
				'Table ' . $name . ' exists');

			$referenceTable = $ns[$name];
			/** @var TableStructure $exploredTable */
			$exploredTable = $exploredStructure[self::TEST_NAMESPACE][$name];

			$comparison = $comparer->compare($referenceTable,
				$exploredTable);

			$txt = $this->stringifyStructureComparison($comparison, true);

			$this->assertCount(0, $comparison,
				'Created table ' . $name . ' is matching the reference' .
				PHP_EOL . $txt);
		}
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
	 * @var \ArrayObject
	 */
	private $createdTables;

	/**
	 *
	 * @var ConnectionInterface
	 */
	private $connection;
}
