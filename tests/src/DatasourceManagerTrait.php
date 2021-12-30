<?php
namespace NoreSources\Test;

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\ConnectionInterface;
use NoreSources\SQL\Structure\DatasourceStructure;
use NoreSources\SQL\Structure\NamespaceStructure;
use NoreSources\SQL\Structure\StructureSerializerFactory;
use NoreSources\SQL\Structure\TableStructure;
use NoreSources\SQL\Syntax\Statement\Structure\CreateNamespaceQuery;
use NoreSources\SQL\Syntax\Statement\Structure\CreateTableQuery;
use NoreSources\Type\TypeDescription;
use PHPUnit\Framework\TestCase;

trait DatasourceManagerTrait
{

	public function initializeDatasourceManager($basePath = null)
	{
		$this->datasourceManagerDatasources = new \ArrayObject();
		$this->datasourceManagerTestBasePath = ($basePath ? $basePath : __DIR__ .
			'/..');
	}

	public function getDatasource($name, $reload = false)
	{
		if (!$reload &&
			$this->datasourceManagerDatasources->offsetExists($name))
			return $this->datasourceManagerDatasources[$name];

		$filename = realpath(
			$this->datasourceManagerTestBasePath . '/data/structures/' .
			$name . '.xml');

		$this->assertFileExists($filename, $name . ' datasource loading');

		$serializer = StructureSerializerFactory::getInstance();
		$structure = $serializer->structureFromFile($filename);

		$this->assertInstanceOf(DatasourceStructure::class, $structure,
			$name . ' datasource loading');

		$this->datasourceManagerDatasources->offsetSet($name, $structure);

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
	private $datasourceManagerDatasources;

	private $datasourceManagerTestBasePath;

	private static $createdTables;
}
