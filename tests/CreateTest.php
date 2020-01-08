<?php
namespace NoreSources\SQL;

use NoreSources\SQL\Expression\TokenStream;
use NoreSources\SQL\Statement\BuildContext;
use NoreSources\SQL\Structure\TableStructure;
use PHPUnit\Framework\TestCase;

final class CreateTableTest extends \PHPUnit\Framework\TestCase
{

	public function __construct()
	{
		parent::__construct();
		$this->datasources = new DatasourceManager();
		$this->derivedFileManager = new DerivedFileManager();
	}

	public function testCreateTableCompanyTables()
	{
		$structure = $this->datasources->get('Company');
		$builder = new DBMS\Reference\StatementBuilder();

		foreach ([
			'Employees',
			'Hierarchy',
			'Tasks'
		] as $tableName)
		{
			$tableStructure = $structure['ns_unittests'][$tableName];
			$this->assertInstanceOf(TableStructure::class, $tableStructure, 'Finding ' . $tableName);
			$context = new BuildContext($builder);
			$context->setPivot($tableStructure);
			$q = new Statement\CreateTableQuery($tableStructure);
			$stream = new TokenStream();
			$q->tokenize($stream, $context);
			$result = $builder->finalize($stream, $context);
			$sql = \SqlFormatter::format(strval($result), false);
			$this->derivedFileManager->assertDerivedFile($sql, __METHOD__, $tableName, 'sql',
				$tableName . ' SQL');
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