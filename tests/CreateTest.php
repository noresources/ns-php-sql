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

	public function testCreateTableCompanyTask()
	{
		$structure = $this->datasources->get('Company');
		$tableStructure = $structure['ns_unittests']['Tasks'];
		$this->assertInstanceOf(TableStructure::class, $tableStructure);
		$builder = new GenericStatementBuilder();
		$context = new StatementContext($builder);
		$q = new CreateTableQuery($tableStructure);
		$sql = $q->buildExpression($context);
		$this->derivedFileManager->assertDerivedFile($sql, __METHOD__, null, 'sql');
	}

	private $datasources;

	/**
	 * /**
	 *
	 * @var DerivedFileManager
	 */
	private $derivedFileManager;
}