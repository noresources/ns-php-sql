<?php
namespace NoreSources\SQL\Expression;

use NoreSources\SQL;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Expression\Evaluator as X;
use PHPUnit\Framework\TestCase;

final class ExpressionEvaluatorTest extends TestCase
{

	public function __construct()
	{
		$this->datasources = new sql\DatasourceManager();
	}

	public function testBasics()
	{
		$list = [
			'parameter' => [
				':p1',
				Parameter::class
			],
			'simple column' => [
				'column_name',
				Column::class
			],
			'canonical column name' => [
				'schema.table.column_name',
				Column::class
			],
			'parenthesis' => [
				'(12)',
				Surround::class
			]
		];

		foreach ($list as $label => $test)
		{
			$label = $label . ' ' . strval($test[0]);
			$e = Evaluator::evaluate($test[0]);
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
			$e = Evaluator::evaluate($test[0]);
			$this->assertInstanceOf(Value::class, $e, $label);
			if ($e instanceof Value)
			{
				$this->assertEquals($e->getExpressionDataType(), $test[1], $label);
			}
		}
	}

	public function testComplex()
	{
		$list = [
			'binary' => [ ':p1 or :p2', BinaryOperation::class ],
			'complex binary' => [ '(:p1 * 2) or (not :p2)', BinaryOperation::class ],
			'unary' => [ 'not null', UnaryOperation::class ],
			'between' => [ 'peace between 1919 and 1939', Between::class ],
			'not-between' => [ 'peace not between 1940 and 1945', Between::class ],
			'in' => [ 'even in (0, 2, 4, 6, 8, 10)', MemberOf::class ],
			'not iin' => [ 'odd not in (0, 2, 4, 6, 8, 10)', MemberOf::class ],
			'like' => [ "fps like 'DOOM%'", BinaryOperation::class ],
			'alternatives' => ["case column when 1 then 'one' when 2 then 'two'", AlternativeList::class]
		];

		foreach ($list as $label => $test)
		{
			$label = $label . ' ' . strval($test[0]);
			$e = Evaluator::evaluate($test[0]);
			$this->assertInstanceOf($test[1], $e);
		}
	}

	public function testFunctions()
	{
		$list = [
			'simple' => [ 'rand()', 0 ],
			'max' => [ 'max(1, 2)', 2, Value::class, Value::class ],
			'substr' => [
				"substr(:string, 0, 2)", 3,
				Parameter::class,
				Value::class,
				Value::class,
			],
			'complex' => [
				"substr(:string, strpos(:string, ','))", 2,
				Parameter::class,
				FunctionCall::class
			],
			'complex 2' => ['expressions(#2010-07-12#, :p, (:v + 2))', 3,
				Value::class,
				Parameter::class,
				BinaryOperation::class
			],
		];

		foreach ($list as $label => $test)
		{
			$label = $label . ' ' . strval($test[0]);
			$e = Evaluator::evaluate($test[0]);
			$this->assertInstanceOf(FunctionCall::class, $e, $label);
			if ($e instanceof FunctionCall)
			{
				$this->assertCount($test[1], $e->getArguments(), $label . ' number of arguments');
			}

			for ($i = 2; $i < $test[1]; $i++)
			{
				$index = $i - 2;
				$this->assertInstanceOf($test[$i], $e->getArgument($index),
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
			$x = Evaluator::evaluate($timestamp['expression']);
			$this->assertInstanceOf(Value::class, $x, $label . ' class');
			if ($x instanceof Value)
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
				'main' => BinaryOperation::class,
				'left' => Column::class,
				'right' => Value::class
			],
			'short' => [
				'expression' => ["=" => ['column', "'value'"]],
				'main' => BinaryOperation::class,
				'left' => Column::class,
				'right' => Value::class
			],
			'function' => [
				'expression' => ["func()" => [2, 'column', X::literal ('string')]],
				'main' => FunctionCall::class,
				'args' => [Value::class, Column::class, Value::class],
			],
			'in' => [
				'expression' => ["in" => [2, 128, ':param']],
				'main' => MemberOf::class,
				'args' => [Value::class, Value::class, Parameter::class],
			]
		];

		foreach ($expressions as $label => $test)
		{
			$x = Evaluator::evaluate($test['expression']);
			$this->assertInstanceOf($test['main'], $x, "'" . $label . '\' main class');
			if ($x instanceof BinaryOperation)
			{
				if (\array_key_exists('left', $test))
					$this->assertInstanceOf($test['left'], $x->getLeftOperand(), $label . ' left');
				if (\array_key_exists('right', $test))
					$this->assertInstanceOf($test['right'], $x->getRightOperand(), $label . ' right');
			}
			elseif ($x instanceof FunctionCall)
			{
				if (\array_key_exists('args', $test))
				{
					$this->assertCount(count($test['args']), $x->getArguments(),
						$label . ' number of arguments');
					for ($i = 0; $i < count($test['args']); $i++)
					{
						$this->assertInstanceOf($test['args'][$i], $x->getArgument($i),
							$label . ' arg ' . $i);
					}
				}
			}
		}
	}

	/**
	 *
	 * @var sql\DatasourceManager
	 */
	private $datasources;
}
