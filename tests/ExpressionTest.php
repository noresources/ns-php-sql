<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax;

use NoreSources\Container\Container;
use NoreSources\Expression\ProcedureInvocation;
use NoreSources\Expression\Value;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DataTypeProviderInterface;
use NoreSources\SQL\DBMS\Reference\ReferencePlatform;
use NoreSources\SQL\Structure\VirtualStructureResolver;
use NoreSources\SQL\Syntax\Statement\StatementBuilder;
use NoreSources\Test\DatasourceManager;
use PHPUnit\Framework\TestCase;

final class ExpressionTest extends TestCase
{

	public function __construct($name = null, array $data = [],
		$dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->datasources = new DatasourceManager();
	}

	public function testBinaryOperation()
	{
		$op = new BinaryOperation('<>', new Column('table.column'),
			new Value('Foo'));

		$this->assertEquals(BinaryOperation::DIFFER, '<>');

		$this->assertTrue($op->isComparison(),
			'<> is a comparison operator (use of late static binding)');

		$reference = new ReferencePlatform();
		$data = StatementBuilder::getInstance()->build($op, $reference);
		$this->assertEquals('[table].[column] <> \'Foo\'',
			\strval($data));
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
				Group::class
			]
		];

		foreach ($list as $label => $test)
		{
			$label = $label . ' ' . strval($test[0]);
			$e = Evaluator::evaluate($test[0]);
			$this->assertInstanceOf($test[1], $e);
		}
	}

	public function testColumnDatas()
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
				K::DATATYPE_REAL
			],
			'float' => [
				456.789,
				K::DATATYPE_REAL
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
			$this->assertInstanceOf(Data::class, $e, $label);
			if ($e instanceof DataTypeProviderInterface)
			{
				$this->assertEquals($e->getDataType(), $test[1], $label);
			}
		}
	}

	public function testComplex()
	{
		$list = [
			'binary' => [
				':p1 or :p2',
				BinaryOperation::class
			],
			'complex binary' => [
				'(:p1 * 2) or (not :p2)',
				BinaryOperation::class
			],
			'unary' => [
				'not null',
				UnaryOperation::class
			],
			'between' => [
				'peace between 1919 and 1939',
				Between::class
			],
			'not-between' => [
				'peace not between 1940 and 1945',
				Between::class
			],
			'in' => [
				'even in (0, 2, 4, 6, 8, 10)',
				MemberOf::class
			],
			'not iin' => [
				'odd not in (0, 2, 4, 6, 8, 10)',
				MemberOf::class
			],
			'like' => [
				"fps like 'DOOM%'",
				BinaryOperation::class
			],
			'alternatives' => [
				"case column when 1 then 'one' when 2 then 'two'",
				AlternativeList::class
			]
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
			'simple' => [
				'rand()',
				0
			],
			'max' => [
				'max(1, 2)',
				2,
				Data::class,
				Data::class
			],
			'substr' => [
				"substr(:string, 0, 2)",
				3,
				Parameter::class,
				Data::class,
				Data::class
			],
			'meta function' => [
				"@strftime('%Y', timeCol)",
				2,
				Data::class,
				ColumnExpression::class
			],
			'complex' => [
				"substr(:string, strpos(:string, ','))",
				2,
				Parameter::class,
				FunctionCall::class
			],
			'complex 2' => [
				'expressions(#2010-07-12#, :p, (:v + 2))',
				3,
				Data::class,
				Parameter::class,
				BinaryOperation::class
			]
		];

		foreach ($list as $label => $test)
		{
			$label = $label . ' ' . strval($test[0]);
			$e = Evaluator::evaluate($test[0]);
			$this->assertInstanceOf(FunctionCall::class, $e, $label);
			if ($e instanceof FunctionCall)
				$this->assertCount($test[1], $e->getArguments(),
					$label . ' number of arguments');

			if (\strpos($test[0], '@') === 0)
				$this->assertInstanceOf(MetaFunctionCall::class, $e,
					$label);

			for ($i = 2; $i < $test[1]; $i++)
			{
				$index = $i - 2;
				$this->assertInstanceOf($test[$i],
					$e->getArgument($index),
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
			$this->assertInstanceOf(Data::class, $x, $label . ' class');
			if ($x instanceof ColumnData)
			{
				$this->assertEquals(K::DATATYPE_TIMESTAMP,
					$x->getDataType());
			}
		}
	}

	public function testPolishNotation()
	{
		$expressions = [
			'shortest' => [
				'expression' => [
					'column' => "'value'"
				],
				'main' => BinaryOperation::class,
				'left' => Column::class,
				'right' => Data::class
			],
			'short' => [
				'expression' => [
					"=" => [
						'column',
						"'value'"
					]
				],
				'main' => BinaryOperation::class,
				'left' => Column::class,
				'right' => Data::class
			],
			'function' => [
				'expression' => [
					"func()" => [
						2,
						'column',
						new Data('string')
					]
				],
				'main' => ProcedureInvocation::class,
				'args' => [
					Data::class,
					Column::class,
					Data::class
				]
			],
			'in' => [
				'expression' => [
					"in" => [
						2,
						128,
						':param'
					]
				],
				'main' => MemberOf::class,
				'args' => [
					Data::class,
					Data::class,
					Parameter::class
				]
			],
			'smart equal' => [
				'expression' => [
					'~=' => [
						':param',
						7
					]
				],
				'main' => SmartCompare::class,
				'sql' => '$param = 7'
			],
			'smart (not) is null' => [
				'expression' => [
					'!~=' => [
						':param',
						null
					]
				],
				'main' => SmartCompare::class,
				'sql' => '$param IS NOT NULL'
			],
			'smart in (array style)' => [
				'expression' => [
					'~=' => [
						'table.column',
						[
							1,
							"'text'"
						]
					]
				],
				'main' => SmartCompare::class,
				'sql' => '[table].[column] IN (1, \'text\')'
			],
			'smart in (list style)' => [
				'expression' => [
					'~=' => [
						'table.column',
						1,
						"'text'"
					]
				],
				'main' => SmartCompare::class,
				'sql' => '[table].[column] IN (1, \'text\')'
			],
			'not between' => [
				'expression' => [
					"!between" => [
						'architecture',
						16,
						64
					]
				],
				'main' => Between::class,
				'validator' => function ($label, Between $x) {
					$this->assertFalse($x->getToggleState(),
						'NOT between');
					$this->assertInstanceOf(Column::class,
						$x->getLeftOperand(), $label . ' left operand');
				}
			]
		];

		$builder = new StatementBuilder();
		$vsr = new VirtualStructureResolver();
		$platform = new ReferencePlatform();

		foreach ($expressions as $label => $test)
		{
			$x = Evaluator::evaluate($test['expression']);
			$this->assertInstanceOf($test['main'], $x,
				"'" . $label . '\' main class');
			if ($x instanceof BinaryOperation)
			{
				if (\array_key_exists('left', $test))
					$this->assertInstanceOf($test['left'],
						$x->getLeftOperand(), $label . ' left');
				if (\array_key_exists('right', $test))
					$this->assertInstanceOf($test['right'],
						$x->getRightOperand(), $label . ' right');
			}
			elseif ($x instanceof FunctionCall)
			{
				if (\array_key_exists('args', $test))
				{
					$this->assertCount(count($test['args']),
						$x->getArguments(),
						$label . ' number of arguments');
					for ($i = 0; $i < count($test['args']); $i++)
					{
						$this->assertInstanceOf($test['args'][$i],
							$x->getArgument($i), $label . ' arg ' . $i);
					}
				}
			}

			if (($sql = Container::keyValue($test, 'sql')))
			{
				$data = $builder->build($x, $platform, $vsr);
				$this->assertEquals($sql, \strval($data),
					$label . ' SQL');
			}

			if (\is_callable(
				$validator = Container::keyValue($test, 'validator')))
			{
				\call_user_func($validator, $label, $x);
			}
		}
	}

	/**
	 *
	 * @var sql\DatasourceManager
	 */
	private $datasources;
}
