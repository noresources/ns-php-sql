<?php

namespace NoreSources\SQL;

use PHPUnit\Framework\TestCase;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\ExpressionEvaluator as X;
use phpDocumentor\Reflection\Types\Parent_;

final class ExpressionEvaluatorTest extends TestCase
{

	public function __construct()
	{
		$this->datasources = new DatasourceManager();
	}

	public function testBasics()
	{
		$list = [
			'parameter' => [ ':p1', ParameterExpression::class ],
			'simple column' => ['column_name', ColumnExpression::class ],
			'canonical column name' => ['schema.table.column_name', ColumnExpression::class],
			'parenthesis' => ['(12)', ParenthesisExpression::class ],
		];

		$evaluator = new ExpressionEvaluator();

		foreach ($list as $label => $test)
		{
			$label = $label . ' ' . strval($test[0]);
			$e = $evaluator($test[0]);
			$this->assertInstanceOf($test[1], $e);
		}
	}

	public function testComplex()
	{
		$list = [
			'binary' => [ ':p1 or :p2', BinaryOperatorExpression::class ],
			'complex binary' => [ '(:p1 * 2) or (not :p2)', BinaryOperatorExpression::class ],
			'unary' => [ 'not null', UnaryOperatorExpression::class ],
			'between' => [ 'peace between 1940 and 1945', BetweenExpression::class ],
		];

		$evaluator = new ExpressionEvaluator();

		foreach ($list as $label => $test)
		{
			$label = $label . ' ' . strval($test[0]);
			$e = $evaluator($test[0]);
			$this->assertInstanceOf($test[1], $e);
		}
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

	public function testFunctions()
	{
		$list = [
			'simple' => [ 'rand()', 0 ],
			'max' => [ 'max(1, 2)', 2, LiteralExpression::class, LiteralExpression::class ],
			'substr' => [
					"substr(:string, 0, 2)", 3,
					ParameterExpression::class,
					LiteralExpression::class,
					LiteralExpression::class,
			],
			'complex' => [ 
					"substr(:string, strpos(:string, ','))", 2, 
					ParameterExpression::class, 
					FunctionExpression::class 
			],
			'complex 2' => ['expressions(#2010-07-12#, :p, (:v + 2))', 3,
				LiteralExpression::class,
				ParameterExpression::class,
						BinaryOperatorExpression::class
			],
		];

		$evaluator = new ExpressionEvaluator();

		foreach ($list as $label => $test)
		{
			$label = $label . ' ' . strval($test[0]);
			$e = $evaluator($test[0]);
			$this->assertInstanceOf(FunctionExpression::class, $e, $label);
			if ($e instanceof FunctionExpression)
			{
				$this->assertCount($test[1], $e->arguments, $label . ' number of arguments');
			}

			for ($i = 2; $i < $test[1]; $i++)
			{
				$index = $i - 2;
				$this->assertInstanceOf($test[$i], $e->arguments[$index], $label . ' type of argument ' . $index);
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