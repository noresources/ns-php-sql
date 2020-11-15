<?php
namespace NoreSources\SQL;

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\Reference\ReferencePlatform;
use NoreSources\SQL\Syntax\Statement\StatementBuilder;
use NoreSources\SQL\Syntax\Statement\Query\SelectQuery;
use NoreSources\SQL\Syntax\Statement\Structure\CreateIndexQuery;
use NoreSources\SQL\Syntax\Statement\Structure\CreateTableQuery;
use NoreSources\SQL\Syntax\Statement\Structure\CreateViewQuery;
use NoreSources\Test\DatasourceManager;
use NoreSources\Test\DerivedFileManager;

final class CreateTest extends \PHPUnit\Framework\TestCase
{

	public function __construct($name = null, array $data = [],
		$dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->datasources = new DatasourceManager();
		$this->derivedFileManager = new DerivedFileManager(__DIR__);
	}

	public function testCreateIndex()
	{
		$structure = $this->datasources->get('Company');
		$tableStructure = $structure['ns_unittests']['Hierarchy'];
		$platform = new ReferencePlatform();

		$index = new CreateIndexQuery();
		$index->table('Hierarchy')
			->identifier('managed')
			->columns('manageeId')
			->where('managerId > 10');

		$data = StatementBuilder::getInstance()($index, $platform, $tableStructure);

		$sql = \SqlFormatter::format(strval($data), false);
		$this->derivedFileManager->assertDerivedFile($sql, __METHOD__,
			null, 'sql');
	}

	public function testCreateView()
	{
		$structure = $this->datasources->get('Company');
		$tableStructure = $structure['ns_unittests']['Employees'];
		$platform = new ReferencePlatform([],
			[
				[
					[
						K::FEATURE_CREATE,
						K::FEATURE_TEMPORARY
					],
					true
				]
			]);

		$select = new SelectQuery($tableStructure);
		$select->columns('id', 'name')->where([
			'gender' => "'M'"
		]);

		$view = new CreateViewQuery();
		$view->identifier('Males')
			->flags(CreateViewQuery::TEMPORARY)
			->select($select);

		$data =  StatementBuilder::getInstance()($view, $platform, $tableStructure);

		$sql = \SqlFormatter::format(strval($data), false);
		$this->derivedFileManager->assertDerivedFile($sql, __METHOD__,
			null, 'sql');
	}

	public function testCreateIndexFromStructure()
	{
		$structure = $this->datasources->get('Company');
		$platform = new ReferencePlatform();
		$indexStructure = $structure['ns_unittests']['index_employees_name'];
		$this->assertInstanceOf(Structure\IndexStructure::class,
			$indexStructure);

		$q = new CreateIndexQuery();
		$q->setFromIndexStructure($indexStructure);
		$result = StatementBuilder::getInstance()->build($q, $platform,
			$indexStructure->getParentElement());

		$sql = \SqlFormatter::format(strval($result), false);
		$this->derivedFileManager->assertDerivedFile($sql, __METHOD__,
			null, 'sql');
	}

	public function testCreateTableCompanyTables()
	{
		$structure = $this->datasources->get('Company');
		$platform = new ReferencePlatform([],
			[
				'or replace' => [
					[
						K::FEATURE_CREATE,
						K::FEATURE_TABLE,
						K::FEATURE_REPLACE
					],
					true
				]
			]);

		foreach ([
			'Employees',
			'Hierarchy',
			'Tasks'
		] as $tableName)
		{
			$tableStructure = $structure['ns_unittests'][$tableName];
			$this->assertInstanceOf(Structure\TableStructure::class,
				$tableStructure, 'Finding ' . $tableName);
			$q = new CreateTableQuery($tableStructure);
			$q->flags(CreateTableQuery::REPLACE);
			$result =  StatementBuilder::getInstance()($q, $platform, $tableStructure);

			$sql = \SqlFormatter::format(strval($result), false);
			$this->derivedFileManager->assertDerivedFile($sql,
				__METHOD__, $tableName, 'sql', $tableName . ' SQL');
		}
	}

	private $datasources;

	/**
	 * /**
	 *
	 * @var DerivedFileManager
	 */
	private $derivedFileManager;
}