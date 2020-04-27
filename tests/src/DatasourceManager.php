<?php
namespace NoreSources\Test;

use NoreSources\TypeDescription;
use NoreSources\SQL\DBMS\ConnectionHelper;
use NoreSources\SQL\DBMS\ConnectionInterface;
use NoreSources\SQL\Statement\Structure\CreateTableQuery;
use NoreSources\SQL\Structure\DatasourceStructure;
use NoreSources\SQL\Structure\StructureSerializerFactory;
use NoreSources\SQL\Structure\TableStructure;
use PHPUnit\Framework\TestCase;

class DatasourceManager extends \PHPUnit\Framework\TestCase
{

	public function __construct($name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->datasources = new \ArrayObject();
		$this->basePath = __DIR__ . '/..';
	}

	public function get($name)
	{
		if ($this->datasources->offsetExists($name))
			return $this->datasources[$name];

		$filename = realpath($this->basePath . '/data/structures/' . $name . '.xml');

		$this->assertFileExists($filename, $name . ' datasource loading');

		$structure = StructureSerializerFactory::structureFromFile($filename);

		$this->assertInstanceOf(DatasourceStructure::class, $structure,
			$name . ' datasource loading');

		$this->datasources->offsetSet($name, $structure);

		return $structure;
	}

	public static function createTable(TestCase $test, ConnectionInterface $connection,
		TableStructure $tableStructure, $stored = false)
	{
		if ($stored)
		{
			if (!\is_array(self::$createdTables))
				self::$createdTables = [];
			$path = TypeDescription::getName($connection) . $tableStructure->getPath();
			if (\array_key_exists($path, self::$createdTables))
				return true;
		}

		$q = new CreateTableQuery($tableStructure);
		$sql = ConnectionHelper::getStatementData($connection, $q);
		return $connection->executeStatement($sql);
	}

	/**
	 *
	 * @var \ArrayObject
	 */
	private $datasources;

	private $basePath;

	private static $createdTables;
}
