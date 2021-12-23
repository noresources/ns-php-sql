<?php
namespace NoreSources\SQL;

use NoreSources\SQL\DBMS\Reference\ReferencePlatform;
use NoreSources\SQL\Syntax\Statement\StatementBuilder;
use NoreSources\SQL\Syntax\Statement\Structure\RenameColumnQuery;
use NoreSources\Test\DatasourceManager;
use NoreSources\Test\DerivedFileTestTrait;
use NoreSources\Test\SqlFormatter;

final class AlterTest extends \PHPUnit\Framework\TestCase
{

	use DerivedFileTestTrait;

	public function __construct($name = null, array $data = [],
		$dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->datasources = new DatasourceManager();
		$this->initializeDerivedFileTest(__DIR__);
	}

	public function testRenameColumn()
	{
		$structure = $this->datasources->get('Company');
		$tableStructure = $structure['ns_unittests']['Employees'];
		$platform = new ReferencePlatform();

		$q = new RenameColumnQuery();
		$q->forStructure($tableStructure);
		$q->rename('name', 'fullName');

		$builder = StatementBuilder::getInstance();
		$data = $builder($q, $platform, $tableStructure);

		$this->assertDerivedFile(SqlFormatter::format($data), __METHOD__,
			'rename_column', 'sql');
	}

	private $datasources;
}