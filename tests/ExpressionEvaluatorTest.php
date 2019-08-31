<?php

namespace NoreSources\SQL;

use PHPUnit\Framework\TestCase;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\ExpressionEvaluator as X;

final class ExpressionEvaluatorTest extends TestCase
{

	public function __construct()
	{
		$this->datasources = new DatasourceManager();
	}

	public function testLiterals()
	{
		$list = [
				'true string' => ['true', K::DATATYPE_BOOLEAN],
				'true' => [true, K::DATATYPE_BOOLEAN],
				'false string' => ['false', K::DATATYPE_BOOLEAN],
				'false' => [false, K::DATATYPE_BOOLEAN],
				'null string' => ['null', K::DATATYPE_NULL],
				'null' => [null, K::DATATYPE_NULL],
				'int string' => ['123', K::DATATYPE_INTEGER],
				'int' => [123, K::DATATYPE_INTEGER],
				'float string' => ['456.789', K::DATATYPE_FLOAT],
				'float' => [456.789, K::DATATYPE_FLOAT],
				'timestamp' => ['#2012-12-24T16:30:58+01:00#', K::DATATYPE_TIMESTAMP]
		];

		$evaluator = new ExpressionEvaluator();

		foreach ($list as $label => $test)
		{
			$label = $label . ' ' . strval($test[0]);
			$e = $evaluator($test[0]);
			$this->assertInstanceOf(LiteralExpression::class, $e, $label);
			if ($e instanceof LiteralExpression)
			{
				$this->assertEquals($e->type, $test[1], $label);
			}
		}
	}

	public function _test1()
	{
		$structure = $this->datasources->get('Company');
		$tableStructure = $structure['ns_unittests']['Employees'];
		$this->assertInstanceOf(TableStructure::class, $tableStructure);
		$builder = new GenericStatementBuilder();
		$context = new StatementContext($builder);
	}

	/**
	 * @var DatasourceManager
	 */
	private $datasources;
}