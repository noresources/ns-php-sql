<?php
namespace NoreSources\SQL;

use PHPUnit\Framework\TestCase;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\ExpressionEvaluator as X;
use phpDocumentor\Reflection\Types\Parent_;

final class SyntaxEEvaluatorTest extends TestCase
{

	public function __construct()
	{}

	public function testBasicSyntaxs()
	{
		$list = [
			'parameter' => [ ':p1', ParameterExpression::class ],
			'simple column' => ['column_name', ColumnExpression::class ],
			'canonical column name' => ['schema.table.column_name', ColumnExpression::class],
			'parenthesis' => ['(12)', ParenthesisExpression::class ],
		];

		foreach ($list as $label => $test)
		{
			$label = $label . ' ' . strval($test[0]);
			$e = ExpressionEvaluator::evaluate($test[0]);
			$this->assertInstanceOf($test[1], $e);
		}
	}

	public function testComplex()
	{
		$list = [
			'binary' => [ ':p1 or :p2', BinaryOperatorExpression::class ],
			'complex binary' => [ '(:p1 * 2) or (not :p2)', BinaryOperatorExpression::class ],
			'unary' => [ 'not null', UnaryOperatorExpression::class ],
			'between' => [ 'peace between 1919 and 1939', BetweenExpression::class ],
			'not-between' => [ 'peace not between 1940 and 1945', BetweenExpression::class ],
			'in' => [ 'even in (0, 2, 4, 6, 8, 10)', InOperatorExpression::class ],
			'not iin' => [ 'odd not in (0, 2, 4, 6, 8, 10)', InOperatorExpression::class ],
			'like' => [ "fps like 'DOOM%'", BinaryOperatorExpression::class ]
		];

		foreach ($list as $label => $test)
		{
			$label = $label . ' ' . strval($test[0]);
			$e = ExpressionEvaluator::evaluate($test[0]);
			$this->assertInstanceOf($test[1], $e);
		}
	}

	public function testLiterals()
	{
		$list = [
			'true string' => [
				'true',
				K::DATATYPE_BOOLEAN
			],
			'true' => [
				true,
				K::DATATYPE_BOOLEAN
			],
			'false string' => [
				'false',
				K::DATATYPE_BOOLEAN
			],
			'false' => [
				false,
				K::DATATYPE_BOOLEAN
			],
			'null string' => [
				'null',
				K::DATATYPE_NULL
			],
			'null' => [
				null,
				K::DATATYPE_NULL
			],
			'int string' => [
				'123',
				K::DATATYPE_INTEGER
			],
			'int' => [
				123,
				K::DATATYPE_INTEGER
			],
			'float string' => [
				'456.789',
				K::DATATYPE_FLOAT
			],
			'float' => [
				456.789,
				K::DATATYPE_FLOAT
			],
			'timestamp' => [
				'#2012-12-24T16:30:58+01:00#',
				K::DATATYPE_TIMESTAMP
			]
		];

		foreach ($list as $label => $test)
		{
			$label = $label . ' ' . strval($test[0]);
			$e = ExpressionEvaluator::evaluate($test[0]);
			$this->assertInstanceOf(LiteralExpression::class, $e, $label);
			if ($e instanceof LiteralExpression)
			{
				$this->assertEquals($e->getExpressionDataType(), $test[1], $label);
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

		foreach ($list as $label => $test)
		{
			$label = $label . ' ' . strval($test[0]);
			$e = ExpressionEvaluator::evaluate($test[0]);
			$this->assertInstanceOf(FunctionExpression::class, $e, $label);
			if ($e instanceof FunctionExpression)
			{
				$this->assertCount($test[1], $e->arguments, $label . ' number of arguments');
			}

			for ($i = 2; $i < $test[1]; $i++)
			{
				$index = $i - 2;
				$this->assertInstanceOf($test[$i], $e->arguments[$index],
					$label . ' type of argument ' . $index);
			}
		}
	}

	public function testTimestamp()
	{
		$timestamps = [
			[
				'label' => 'Extended date',
				'expression' => '#2017-11-28#'
			],
			[
				'label' => 'Basic date',
				'expression' => '#20171128#'
			],
			[
				'label' => 'Date & time in local timezone',
				'expression' => '#2019-03-10 15:16:59#'
			],
			[
				'label' => 'Date & time in UTC',
				'expression' => '#2019-03-10 15:16:59Z#'
			],
			[
				'label' => 'Date & time in a given timezone',
				'expression' => '#2019-03-10 15:16:59-0700#'
			],
			[
				'label' => 'Hour & minutes',
				'expression' => '#13:37#'
			],
			[
				'label' => 'Time with second fraction',
				'expression' => '#13:37:39.256#'
			]
		];

		foreach ($timestamps as $label => $timestamp)
		{
			$x = ExpressionEvaluator::evaluate($timestamp['expression']);
			$this->assertInstanceOf(LiteralExpression::class, $x, $label . ' class');
			if ($x instanceof LiteralExpression)
			{
				$this->assertEquals(K::DATATYPE_TIMESTAMP, $x->getExpressionDataType());
			}
		}
	}

	public function testPolishNotation()
	{
		$expressions = [
			'shortest' => [
				'expression' => ['column' => "'value'"],
				'main' => BinaryOperatorExpression::class,
				'left' => ColumnExpression::class,
				'right' => LiteralExpression::class
			],
			'short' => [
					'expression' => ["=" => ['column', "'value'"]],
					'main' => BinaryOperatorExpression::class,
					'left' => ColumnExpression::class,
					'right' => LiteralExpression::class
			],
			'function' => [
					'expression' => ["func()" => [2, 'column', X::literal ('string')]],
					'main' => FunctionExpression::class,
					'args' => [LiteralExpression::class, ColumnExpression::class, LiteralExpression::class],
			],
			'in' => [
				'expression' => ["in" => [2, 128, ':param']],
				'main' => InOperatorExpression::class,
				'args' => [LiteralExpression::class, LiteralExpression::class, ParameterExpression::class],
			]
		];

		foreach ($expressions as $label => $test)
		{
			$x = ExpressionEvaluator::evaluate($test['expression']);
			$this->assertInstanceOf($test['main'], $x, "'" . $label . '\' main class');
			if ($x instanceof BinaryOperatorExpression)
			{
				if (\array_key_exists('left', $test))
					$this->assertInstanceOf($test['left'], $x->leftOperand, $label . ' left');
				if (\array_key_exists('right', $test))
					$this->assertInstanceOf($test['right'], $x->rightOperand, $label . ' right');
			}
			elseif ($x instanceof FunctionExpression)
			{
				if (\array_key_exists('args', $test))
				{
					$this->assertCount(count($test['args']), $x->arguments,
						$label . ' number of arguments');
					for ($i = 0; $i < count($test['args']); $i++)
					{
						$this->assertInstanceOf($test['args'][$i], $x->arguments[$i],
							$label . ' arg ' . $i);
					}
				}
			}
		}
	}
}