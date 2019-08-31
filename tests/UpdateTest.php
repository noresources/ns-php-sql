<?php

namespace NoreSources\SQL;

use PHPUnit\Framework\TestCase;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\ExpressionEvaluator as X;

final class UpdateTest extends TestCase
{

	public function __construct()
	{
		parent::__construct();
		$this->derivedFileManager = new DerivedFileManager();
		$this->datasources = new DatasourceManager();
	}

	public function testUpdateBasic()
	{}

	public function testUpdateCompanyEmployees()
	{
		$structure = $this->datasources->get('Company');
		$tableStructure = $structure['ns_unittests']['Employees'];
		$this->assertInstanceOf(TableStructure::class, $tableStructure);
		$builder = new GenericStatementBuilder();
		$context = new StatementContext($builder);
		$context->setPivot ($tableStructure);
		$q = new UpdateQuery($tableStructure);

		$q->set('salary', 'salary * 2');
		$q->where ('id=1');

		$sql = $q->buildExpression($context);

		$this->derivedFileManager->assertDerivedFile($sql, __METHOD__, null, 'sql');
	}

	/**
	 * @var DatasourceManager
	 */
	private $datasources;

	/**
	 * @var DerivedFileManager
	 */
	private $derivedFileManager;
}