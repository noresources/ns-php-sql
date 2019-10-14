<?php
namespace NoreSources\SQL;

use PHPUnit\Framework\TestCase;

final class CreateTableTest extends TestCase
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
		$builder = new Reference\StatementBuilder();

		foreach ([
			'Employees',
			'Hierarchy',
			'Tasks'
		] as $tableName)
		{
			$tableStructure = $structure['ns_unittests'][$tableName];
			$this->assertInstanceOf(TableStructure::class, $tableStructure, 'Finding ' . $tableName);
			$context = new StatementContext($builder);
			$context->setPivot($tableStructure);
			$q = new CreateTableQuery($tableStructure);
			$stream = new TokenStream();
			$q->tokenize($stream, $context);
			$sql = $builder->finalize($stream, $context);
			$sql = \SqlFormatter::format(strval($sql), false);
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