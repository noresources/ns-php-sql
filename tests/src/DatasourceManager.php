<?php
namespace NoreSources\Test;

use NoreSources\TypeDescription;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\ConnectionInterface;
use NoreSources\SQL\Structure\DatasourceStructure;
use NoreSources\SQL\Structure\NamespaceStructure;
use NoreSources\SQL\Structure\StructureSerializerFactory;
use NoreSources\SQL\Structure\TableStructure;
use NoreSources\SQL\Syntax\Statement\Structure\CreateNamespaceQuery;
use NoreSources\SQL\Syntax\Statement\Structure\CreateTableQuery;
use PHPUnit\Framework\TestCase;

class DatasourceManager extends \PHPUnit\Framework\TestCase
{

	public function __construct($name = null, array $data = [],
		$dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->datasources = new \ArrayObject();
		$this->basePath = __DIR__ . '/..';
	}

	public function get($name, $reload = false)
	{
		if (!$reload && $this->datasources->offsetExists($name))
			return $this->datasources[$name];

		$filename = realpath(
			$this->basePath . '/data/structures/' . $name . '.xml');

		$this->assertFileExists($filename, $name . ' datasource loading');

		$serializer = StructureSerializerFactory::getInstance();
		$structure = $serializer->structureFromFile($filename);

		$this->assertInstanceOf(DatasourceStructure::class, $structure,
			$name . ' datasource loading');

		$this->datasources->offsetSet($name, $structure);

		return $structure;
	}

	public static function createTable(TestCase $test,
		ConnectionInterface $connection, TableStructure $tableStructure,
		$stored = false, $flags = K::CREATE_EXISTS_CONDITION)
	{
		if ($stored)
		{
			if (!\is_array(self::$createdTables))
				self::$createdTables = [];
			$path = TypeDescription::getName($connection) .
				$tableStructure->getPath();
			if (\array_key_exists($path, self::$createdTables))
				return true;
		}

		$platform = $connection->getPlatform();

		$ns = $tableStructure->getParentElement();

		if ($platform->hasStatement(CreateNamespaceQuery::class) &&
			$ns instanceof NamespaceStructure)
		{
			/** @var CreateNamespaceQuery $cns */
			$cns = $platform->newStatement(CreateNamespaceQuery::class);

			$flags = $cns->getCreateFlags();
			$flags |= K::FEATURE_CREATE_EXISTS_CONDITION;
			$cns->identifier($ns->getName())
				->createFlags($flags);
			try
			{
				$sql = ConnectionHelper::buildStatement($connection,
					$cns);
				$connection->executeStatement($sql);
			}
			catch (\Exception $e)
			{}
		}

		/**  @var CreateTableQuery */
		$q = $platform->newStatement(CreateTableQuery::class);
		$q->table($tableStructure);
		$q->createFlags($flags);
		$sql = ConnectionHelper::buildStatement($connection, $q);
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
