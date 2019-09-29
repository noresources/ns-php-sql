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
		$builder = new Reference\StatementBuilder();
		$context = new StatementContext($builder);
		$context->setPivot($tableStructure);
		$q = new CreateTableQuery($tableStructure);
		$stream = new TokenStream();
		$q->tokenize($stream, $context);
		$sql = $builder->buildStatementData($stream);
		$sql = \SqlFormatter::format(strval($sql), false);
		//$sql = $q->buildExpression($context);
		$this->derivedFileManager->assertDerivedFile($sql, __METHOD__, null, 'sql', 'Create task');
	}

	private $datasources;

	/**
	 * /**
	 *
	 * @var DerivedFileManager
	 */
	private $derivedFileManager;
}