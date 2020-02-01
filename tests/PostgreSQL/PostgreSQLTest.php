<?php
namespace NoreSources\SQL;

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\ConnectionHelper;
use NoreSources\SQL\DBMS\PostgreSQL\PostgreSQLStatementBuilder;
use NoreSources\SQL\Statement\CreateTableQuery;
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

		$connection = ConnectionHelper::createConnection(
			[
				K::CONNECTION_PARAMETER_TYPE => \NoreSources\SQL\DBMS\PostgreSQL\PostgreSQLConnection::class
			]);

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
