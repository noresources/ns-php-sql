<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax;

use Ferno\Loco;
use Ferno\Loco\EmptyParser;
use Ferno\Loco\LazyAltParser;
use Ferno\Loco\StringParser;
use NoreSources\Bitset;
use NoreSources\BooleanRepresentation;
use NoreSources\Container;
use NoreSources\FloatRepresentation;
use NoreSources\IntegerRepresentation;
use NoreSources\SingletonTrait;
use NoreSources\TypeDescription;
use NoreSources\Expression\ExpressionInterface;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DataTypeProviderInterface;

/**
 * Exception raised when an expression failed to be evaluated
 */
class EvaluatorException extends \ErrorException
{

	public function __construct($message)
	{
		parent::__construct($message);
	}
}

/**
 * ExpressionInterface evaluator
 */
class Evaluator
{

	use SingletonTrait;

	public function __construct()
	{
		$this->evaluatorFlags = 0;
		$this->operators = [
			1 => [
				'not' => new UnaryPolishNotationOperation('NOT',
					PolishNotationOperation::KEYWORD |
					PolishNotationOperation::POST_SPACE),
				'!' => new UnaryPolishNotationOperation('NOT',
					PolishNotationOperation::KEYWORD),
				'-' => new UnaryPolishNotationOperation('-'),
				'~' => new UnaryPolishNotationOperation('~')
			],
			2 => [
				'==' => new BinaryPolishNotationOperation('='),
				'<>' => new BinaryPolishNotationOperation('<>'),
				'!=' => new BinaryPolishNotationOperation('<>'),
				'<=' => new BinaryPolishNotationOperation('<='),
				'<<' => new BinaryPolishNotationOperation('<<'),
				'>>' => new BinaryPolishNotationOperation('>>'),
				'>=' => new BinaryPolishNotationOperation('>='),
				'=' => new BinaryPolishNotationOperation('='),
				'<' => new BinaryPolishNotationOperation('<'),
				'>' => new BinaryPolishNotationOperation('>'),
				'&' => new BinaryPolishNotationOperation('&'),
				'|' => new BinaryPolishNotationOperation('|'),
				'^' => new BinaryPolishNotationOperation('^'),
				'-' => new BinaryPolishNotationOperation('-'),
				'+' => new BinaryPolishNotationOperation('+'),
				'*' => new BinaryPolishNotationOperation('*'),
				'/' => new BinaryPolishNotationOperation('/'),
				'%' => new BinaryPolishNotationOperation('%'),
				'and' => new BinaryPolishNotationOperation('AND',
					PolishNotationOperation::KEYWORD |
					PolishNotationOperation::SPACE),
				'is' => new BinaryPolishNotationOperation('IS',
					PolishNotationOperation::KEYWORD |
					PolishNotationOperation::SPACE),
				'!is' => new BinaryPolishNotationOperation('IS NOT',
					PolishNotationOperation::KEYWORD |
					PolishNotationOperation::SPACE),
				'is not' => new BinaryPolishNotationOperation('IS NOT',
					PolishNotationOperation::KEYWORD |
					PolishNotationOperation::SPACE),
				'or' => new BinaryPolishNotationOperation('OR',
					PolishNotationOperation::KEYWORD |
					PolishNotationOperation::SPACE),
				'like' => new BinaryPolishNotationOperation('LIKE',
					PolishNotationOperation::KEYWORD |
					PolishNotationOperation::SPACE),
				'~=' => new PolishNotationOperation(null, 0,
					SmartCompare::class)
			],
			'*' => [
				'()' => new PolishNotationOperation(null, 0,
					Group::class),
				'between' => new PolishNotationOperation(null,
					PolishNotationOperation::PRE_SPACE |
					PolishNotationOperation::POST_WHITESPACE,
					Between::class),
				'in' => new PolishNotationOperation(null,
					PolishNotationOperation::PRE_SPACE |
					PolishNotationOperation::POST_WHITESPACE |
					PolishNotationOperation::KEYWORD, MemberOf::class),
				'~=' => new PolishNotationOperation(null, 0,
					SmartCompare::class)
			]
		];
	}

	/**
	 * Evalute expression description
	 *
	 * @param string|mixed $string
	 * @return mixed
	 */
	public function __invoke($evaluable)
	{
		return $this->evaluate($evaluable);
	}

	/**
	 *
	 * @method ExpressionInterface evaluate ($evaluable)
	 *
	 * @param string $name
	 * @param array $args
	 *
	 * @return ExpressionInterface
	 *
	 * @throws \BadMethodCallException
	 */
	public function __call($name, $args)
	{
		if ($name == 'evaluate')
		{
			return call_user_func_array([
				$this,
				'evaluateEvaluable'
			], $args);
		}

		throw new \BadMethodCallException(
			$name . ' is not a valid method name');
	}

	/**
	 *
	 * @method ExpressionInterface evaluate ($evaluable)
	 *
	 * @param string $name
	 * @param array $args
	 *        	enizableExpressionInterface
	 *
	 * @throws \BadMethodCallException
	 */
	public static function __callStatic($name, $args)
	{
		if ($name == 'evaluate')
		{
			return call_user_func_array(
				[
					self::getInstance(),
					'evaluateEvaluable'
				], $args);
		}

		throw new \BadMethodCallException(
			$name . ' is not a valid method name');
	}

	/**
	 *
	 * @param mixed $expression
	 *        	Any object
	 * @return integer Data type identifier
	 */
	public static function getDataType($expression)
	{
		if ($expression instanceof DataTypeProviderInterface)
			return $expression->getDataType();
		elseif (\is_object($expression))
		{
			if ($expression instanceof \DateTimeInterface)
				return K::DATATYPE_TIMESTAMP;
			elseif (TypeDescription::hasStringRepresentation(
				$expression))
				return K::DATATYPE_STRING;
			elseif ($expression instanceof FloatRepresentation)
				return K::DATATYPE_FLOAT;
			elseif ($expression instanceof IntegerRepresentation)
				return K::DATATYPE_INTEGER;
			elseif ($expression instanceof BooleanRepresentation)
				return K::DATATYPE_BOOLEAN;
		}

		if (\is_integer($expression))
			return K::DATATYPE_INTEGER;
		elseif (\is_float($expression))
			return K::DATATYPE_FLOAT;
		elseif (\is_bool($expression))
			return K::DATATYPE_BOOLEAN;
		elseif (\is_null($expression))
			return K::DATATYPE_NULL;
		elseif (\is_string($expression))
			return K::DATATYPE_STRING;

		return K::DATATYPE_UNDEFINED;
	}

	// bm3
	/**
	 *
	 * @param string|array $evaluable
	 */
	public function evaluateEvaluable($evaluable)
	{
		if (\is_object($evaluable))
		{
			if ($evaluable instanceof ExpressionInterface)
				return $evaluable;
			elseif ($evaluable instanceof \DateTimeInterface)
				return new Data($evaluable, K::DATATYPE_TIMESTAMP);
		}
		elseif (\is_null($evaluable))
			return new Data($evaluable, K::DATATYPE_NULL);
		elseif (\is_bool($evaluable))
			return new Data($evaluable, K::DATATYPE_BOOLEAN);
		elseif (is_int($evaluable))
			return new Data($evaluable, K::DATATYPE_INTEGER);
		elseif (is_float($evaluable))
			return new Data($evaluable, K::DATATYPE_FLOAT);
		elseif (\is_numeric($evaluable))
		{
			$i = \intval($evaluable);
			$f = \floatval($evaluable);
			if ($i == $f)
				return new Data($i, K::DATATYPE_INTEGER);
			else
				return new Data($f, K::DATATYPE_FLOAT);
		}
		elseif (is_string($evaluable))
		{
			return $this->evaluateString($evaluable);
		}
		elseif (\is_array($evaluable))
		{
			if (Container::isAssociative($evaluable))
			{
				// Polish notation or "column => value"
				if (\count($evaluable) == 1)
				{
					// column = value
					list ($a, $b) = Container::first($evaluable);
					if (!\is_array($b))
					{
						return new BinaryOperation(
							BinaryOperation::EQUAL, $this->evaluate($a),
							$this->evaluate($b));
					}

					return $this->evaluatePolishNotationElement($a, $b);
				}

				throw new EvaluatorException('Unsupported syntax');
			}
			else
			{
				return new ExpressionList(
					Container::map($evaluable,
						function ($k, $v) {
							return $this->evaluateEvaluable($v);
						}));
			}
		}

		throw new EvaluatorException(
			TypeDescription::getName($evaluable) . ' cannot be evaluated');
	}

	/**
	 * Private method
	 *
	 * @param string $keyword
	 * @param boolean $positiveCallable
	 * @param boolean $negativeCallable
	 * @return \Ferno\Loco\LazyAltParser
	 */
	public static function negatableKeywordParser($keyword,
		$positiveCallable = true, $negativeCallable = false)
	{
		$nc = $negativeCallable;
		if (!\is_callable($negativeCallable))
		{
			$nc = function () use ($negativeCallable) {
				return $negativeCallable;
			};
		}

		return new Loco\LazyAltParser(
			[
				new Loco\ConcParser(
					[
						self::keywordParser('not'),
						'space',
						self::keywordParser($keyword)
					], $nc),
				self::keywordParser($keyword, $positiveCallable)
			]);
	}

	/**
	 * Private method
	 *
	 * @param string $keyword
	 * @param callable $callable
	 * @return \Ferno\Loco\LazyAltParser
	 */
	public static function keywordParser($keyword, $callable = null)
	{
		$c = $callable;
		if (!\is_callable($callable))
		{
			$c = function () use ($callable) {
				return $callable;
			};
		}

		return new Loco\LazyAltParser(
			[
				new Loco\StringParser(strtolower($keyword)),
				new Loco\StringParser(strtoupper($keyword))
			], $c);
	}

	/**
	 * Evaluate a polish notation form expression
	 *
	 * @param string $key
	 *        	Lower case operator or function name
	 * @param array $operands
	 */
	private function evaluatePolishNotationElement($key, $operands)
	{
		$key = \trim($key);
		$toggleState = true;
		$length = \strlen($key);
		if (\strpos($key, 'not ') === 0)
		{
			$toggleState = false;
			$key = \ltrim(\substr($key, 3));
		}
		elseif (\strpos($key, '!') === 0)
		{
			$toggleState = false;
			$key = \substr($key, 1);
		}

		/*
		 *  Automatically fix missing  [] around polish operation operands
		 */
		if (\count($operands) == 1 && Container::isAssociative(
			$operands))
			$operands = [
				$operands
			];

		$operands = \array_map(
			function ($operand) {
				return $this->evaluateEvaluable($operand);
			}, $operands);

		// Function
		if (\strpos($key, '()') === ($length - 2))
		{
			if (\strpos($key, '@') === 0)
				return new MetaFunctionCall(
					substr($key, 1, $length - 3), $operands);

			return new FunctionCall(substr($key, 0, $length - 2),
				$operands);
		}

		$c = \count($operands);
		$o = false;

		if (Container::keyExists($this->operators, $c))
			$o = Container::keyValue($this->operators[$c], $key, false);

		if (!($o instanceof PolishNotationOperation))
			$o = Container::keyValue($this->operators['*'], $key, false);

		if (!($o instanceof PolishNotationOperation))
			throw new EvaluatorException(
				'Unable to evalate Polish notation "' . $key . '" => [' .
				$c . ' argument(s)... ]');

		$cls = new \ReflectionClass($o->className);

		if ($o->operator)
			array_unshift($operands, $o->operator);
		$instance = null;
		if ($cls->hasMethod('createWithParameterList'))
			$instance = $cls->getMethod('createWithParameterList')->invokeArgs(
				null, $operands);
		else
			$instance = $cls->newInstanceArgs($operands);
		if ($instance instanceof ToggleableInterface)
			$instance->toggle($toggleState);
		return $instance;
	}

	/**
	 *
	 * @param string $string
	 * @return mixed
	 */
	private function evaluateString($string)
	{
		if (!($this->evaluatorFlags & self::GRAMMAR_BUILT))
		{
			$this->buildGrammar();
		}

		try
		{
			return $this->grammar->parse($string);
		}
		catch (Loco\ParseFailureException $e)
		{
			throw new EvaluatorException($e->getMessage());
		}
	}

	private function buildGrammar()
	{
		$rx = [
			self::PATTERN_IDENTIFIER => '[a-zA-Z_@#][a-zA-Z0-9_@#]*',
			self::PATTERN_FUNCTION_NAME => '[a-zA-Z_][a-zA-Z0-9_]*',
			self::PATTERN_PARAMETER_NAME => '[a-zA-Z0-9_]+',
			self::PATTERN_SPACE => '[ \n\r\t]+',
			self::PATTERN_WHITESPACE => '[ \n\r\t]*',
			self::PATTERN_NUMBER => '-?(0|[1-9][0-9]*)(\.[0-9]+)?([eE][-+]?[0-9]+)?'
		];

		$any = new Loco\Utf8Parser();
		$whitespace = new Loco\RegexParser(
			chr(1) . '^(' . $rx[self::PATTERN_WHITESPACE] . ')' . chr(1),
			function () {
				return '';
			});

		$space = new Loco\RegexParser(
			chr(1) . '^(' . $rx[self::PATTERN_SPACE] . ')' . chr(1),
			function () {
				return '';
			});

		$parameterName = new Loco\RegexParser(
			chr(1) . '^:(' . $rx[self::PATTERN_PARAMETER_NAME] . ')' .
			chr(1),
			function ($full, $name) {
				return new Parameter($name);
			});
		$functionName = new Loco\RegexParser(
			chr(1) . '^(' . $rx[self::PATTERN_FUNCTION_NAME] . ')' .
			chr(1));
		$identifier = new Loco\RegexParser(
			chr(1) . '^(' . $rx[self::PATTERN_IDENTIFIER] . ')' . chr(1),
			function ($all, $name) {
				return $name;
			});

		$subpath = new Loco\ConcParser(
			[
				new Loco\StringParser('.'),
				'identifier'
			],
			function ($dot, $identifier) {
				return $dot . $identifier;
			});

		$path = new Loco\LazyAltParser(
			[
				new Loco\ConcParser(
					[
						'identifier',
						new Loco\GreedyMultiParser($subpath, 1, 3,
							function () {
								return implode('', func_get_args());
							})
					], function ($a, $b) {
						return $a . $b;
					}),
				'identifier'
			], function ($p) {
				return new Column($p);
			});

		$commaExpression = new Loco\ConcParser(
			[
				new Loco\StringParser(','),
				'whitespace',
				'expression'
			], function ($c, $w, $expression) {
				return $expression;
			});

		$commaExpressionList = new Loco\GreedyMultiParser(
			'comma-expression', 1, null);

		$expressionList = new Loco\LazyAltParser(
			[
				new Loco\ConcParser(
					[
						'expression',
						'comma-expression-list'
					],
					function ($first, $others) {
						$a = [
							$first
						];
						return array_merge($a, $others);
					}),
				'expression',
				new Loco\EmptyParser()
			],
			function ($a) {
				if (\is_null($a))
					return [];
				elseif ($a instanceof ExpressionInterface)
					return [
						$a
					];
				return $a;
			});

		$procedure = new Loco\ConcParser(
			[
				new LazyAltParser(
					[
						new StringParser('@'),
						new EmptyParser()
					]),
				'function-name',
				'whitespace',
				new Loco\StringParser('('),
				'whitespace',
				'expression-list',
				'whitespace',
				new Loco\StringParser(')')
			],
			function ($meta, $name, $w1, $s1, $w2, $args) {
				if ($meta == '@')
					return new MetaFunctionCall($name, $args);
				return new FunctionCall($name, $args);
			});

		$parenthesis = new Loco\ConcParser(
			[
				new Loco\StringParser('('),
				'whitespace',
				'complex-expression',
				'whitespace',
				new Loco\StringParser(')')
			], function () {
				return new Group(func_get_arg(2));
			});

		$whenThen = new Loco\ConcParser(
			[
				self::keywordParser('when'),
				'space',
				'expression',
				'space',
				self::keywordParser('then'),
				'space',
				'expression'
			],
			function () {
				return new Alternative(func_get_arg(2), func_get_arg(6));
			});

		$moreWhenThen = new Loco\GreedyMultiParser(
			new Loco\ConcParser([
				'space',
				'when-then'
			], function () {
				return func_get_arg(1);
			}), 0, null);

		$else = new Loco\ConcParser(
			[
				'space',
				self::keywordParser('else'),
				'space',
				'expression'
			], function () {
				return func_get_arg(3);
			});

		$case = new Loco\ConcParser(
			[
				self::keywordParser('case'),
				'space',
				'expression',
				'space',
				'when-then',
				'when-then-star',
				new Loco\GreedyMultiParser($else, 0, 1,
					function () {
						$n = func_num_args();
						return ($n > 0) ? func_get_arg(0) : null;
					})
			],
			function () {
				$c = new AlternativeList(func_get_arg(2));
				$c->appendAlternative(func_get_arg(4));
				foreach (func_get_arg(5) as $wt)
				{
					$c->appendAlternative($wt);
				}
				$c->setOtherwise(func_get_arg(6));
				return $c;
			});

		$stringContent = new Loco\GreedyStarParser(
			new Loco\LazyAltParser(
				[
					new Loco\Utf8Parser([
						"'"
					]),
					new Loco\StringParser("''",
						function () {
							return "'";
						})
				]), function () {
				return implode('', func_get_args());
			});

		$string = new Loco\ConcParser(
			[
				new Loco\StringParser("'"),
				$stringContent,
				new Loco\StringParser("'")
			],
			function () {
				return new Data(func_get_arg(1), K::DATATYPE_STRING);
			});

		// Date & Time

		$year = new Loco\RegexParser(
			chr(1) . '^(\+|-)?[0-9]{1,4}' . chr(1));
		$month = new Loco\LazyAltParser(
			[
				new Loco\RegexParser(chr(1) . '^1[0-2]' . chr(1)),
				new Loco\RegexParser(chr(1) . '^0[0-9]' . chr(1))
			]);
		$day = new Loco\LazyAltParser(
			[
				new Loco\RegexParser(chr(1) . '^3[0-1]' . chr(1)),
				new Loco\RegexParser(chr(1) . '^[0-2][0-9]' . chr(1))
			]);
		$hour = new Loco\LazyAltParser(
			[
				new Loco\RegexParser(chr(1) . '^2[0-3]' . chr(1)),
				new Loco\RegexParser(chr(1) . '^[0-1][0-9]' . chr(1))
			]);

		$minutes = new Loco\RegexParser(chr(1) . '^[0-5][0-9]' . chr(1));

		$seconds = new Loco\LazyAltParser(
			[
				new Loco\ConcParser(
					[
						$minutes,
						new Loco\StringParser('.'),
						new Loco\RegexParser(
							chr(1) . '^[0-1][0-9][0-9]' . chr(1))
					], function ($m, $_d, $s) {
						return [
							$m,
							$s
						];
					}),
				$minutes
			]);

		$baseDate = new Loco\ConcParser([
			$year,
			$month,
			$day
		]);
		$extendedDate = new Loco\ConcParser(
			[
				$year,
				new Loco\StringParser('-'),
				$month,
				new Loco\StringParser('-'),
				$day
			],
			function ($y, $_1, $m, $_2, $d) {
				return [
					$y,
					$m,
					$d
				];
			});

		$dateSeparator = new Loco\StringParser('-');
		$optionalDateSeparator = new Loco\LazyAltParser(
			[
				$dateSeparator,
				new Loco\EmptyParser()
			]);
		$date = new Loco\LazyAltParser(
			[
				new Loco\ConcParser(
					[
						$year,
						$optionalDateSeparator,
						$month,
						$optionalDateSeparator,
						$day
					],
					function ($y, $a, $m, $b, $d) {
						return [
							'year' => $y,
							'month' => $m,
							'day' => $d
						];
					}),
				// YYYY-MM
				new Loco\ConcParser([
					$year,
					$dateSeparator,
					$month
				],
					function ($y, $a, $m) {
						return [
							'year' => $y,
							'month' => $m,
							'day' => date('d')
						];
					}),
				// --MM-DD
				new Loco\ConcParser(
					[
						new Loco\StringParser('--'),
						$month,
						$optionalDateSeparator,
						$day
					],
					function ($a, $m, $b, $d) {
						return [
							'year' => date('y'),
							'month' => $m,
							'day' => $d
						];
					})
			]);

		$optionalTimeSeparator = new Loco\LazyAltParser(
			[
				new Loco\StringParser(':'),
				new Loco\EmptyParser()
			]);

		// .sss
		$optionalTimeFraction = new Loco\LazyAltParser(
			[
				new Loco\ConcParser(
					[
						new Loco\LazyAltParser(
							[
								new Loco\StringParser('.'),
								new Loco\StringParser(',')
							]),
						new Loco\RegexParser(
							chr(1) . '^[0-9]+' . chr(1))
					],
					function ($s, $f) {
						$length = strlen($f);
						return intval($f) * pow(10, -$length);
					}),
				new Loco\EmptyParser(function () {
					return 0;
				})
			]);

		$time = new Loco\LazyAltParser(
			[
				// hh:mm:ss[.sss]
				new Loco\ConcParser(
					[
						$hour,
						$optionalTimeSeparator,
						$minutes,
						$optionalTimeSeparator,
						$seconds,
						$optionalTimeFraction
					],
					function ($h, $a, $m, $b, $s, $f) {
						return [
							'hour' => $h,
							'minute' => $m,
							'second' => $s,
							'microsecond' => $f * 1000000
						];
					}),
				// hh:mm
				new Loco\ConcParser(
					[
						$hour,
						$optionalTimeSeparator,
						$minutes
					],
					function ($h, $a, $m) {
						return [
							'hour' => $h,
							'minute' => $m,
							'second' => '00',
							'microsecond' => 0
						];
					}),
				// hh
				new Loco\ConcParser([
					$hour
				],
					function ($h) {
						return [
							'hour' => $h,
							'minute' => '00',
							'second' => '00',
							'microsecond' => 0
						];
					})
			]);

		$timezone = new Loco\LazyAltParser(
			[
				// Z (UTC)
				new Loco\StringParser('Z',
					function () {
						return [
							'timezone' => '+0000'
						];
					}),
				// Hour & minutes
				new Loco\ConcParser(
					[
						new Loco\RegexParser(chr(1) . '^\+|-' . chr(1)),
						$hour,
						$optionalTimeSeparator,
						$minutes
					],
					function ($s, $h, $_1, $m) {
						return [
							'timezone' => $s . $h . $m
						];
					}),
				// hour
				new Loco\ConcParser(
					[
						new Loco\RegexParser(chr(1) . '^\+|-' . chr(1)),
						$hour
					],
					function ($s, $h) {
						return [
							'timezone' => $s . $h . '00'
						];
					})
			]);

		$dateTimeSeparator = new Loco\LazyAltParser(
			[
				new Loco\StringParser('T'),
				$space
			]);

		$dateTime = new Loco\LazyAltParser(
			[
				new Loco\ConcParser(
					[
						$date,
						$dateTimeSeparator,
						$time,
						$timezone
					],
					function ($d, $s, $t, $z) {
						return array_merge($d, $t, $z);
					}),
				new Loco\ConcParser(
					[
						$date,
						$dateTimeSeparator,
						$time
					],
					function ($d, $s, $t) {
						return array_merge($d, $t);
					}),
				$date,
				new Loco\ConcParser([
					$time,
					$timezone
				], function ($t, $z) {
					return array_merge($t, $z);
				}),
				$time
			]);

		$timestamp = new Loco\ConcParser(
			[
				new Loco\StringParser('#'),
				$dateTime,
				new Loco\StringParser('#')
			],
			function ($a, $dt, $b) {
				$timezone = date('O');
				$date = date('Y-m-d');
				$time = date('H:i:s.u');

				if (Container::keyExists($dt, 'hour'))
				{
					$time = $dt['hour'] . ':' . $dt['minute'] . ':' .
					$dt['second'] . '.' . $dt['microsecond'];
				}

				if (Container::keyExists($dt, 'year'))
				{
					$date = $dt['year'] . '-' . $dt['month'] . '-' .
					$dt['day'];
				}

				if (Container::keyExists($dt, 'timezone'))
				{
					$timezone = $dt['timezone'];
				}

				$dateTimeString = $date . 'T' . $time . $timezone;
				$dateTime = \DateTime::createFromFormat(
					self::DATETIME_FORMAT, $dateTimeString);

				return new Data($dateTime, K::DATATYPE_TIMESTAMP);
			});

		$number = new Loco\RegexParser(
			chr(1) . '^(' . $rx[self::PATTERN_NUMBER] . ')' . chr(1),
			function ($full, $v) {
				$i = \intval($v);
				$f = \floatval($v);
				if ($i == $v)
					return new Data($i, K::DATATYPE_INTEGER);
				else
					return new Data($f, K::DATATYPE_FLOAT);
			});

		$unaryOperators = [];
		foreach ($this->operators[1] as $key => $po)
		{
			$unaryOperators[] = $po->createParser($key);
		}

		$unaryOperatorColumnData = new Loco\LazyAltParser(
			$unaryOperators);

		$unaryOperation = new Loco\ConcParser(
			[
				'unary-operator-literal',
				'expression'
			],
			function ($o, $operand) {
				return new UnaryOperation(strtolower($o), $operand);
			});

		$binaryOperators = [];
		foreach ($this->operators[2] as $key => $po)
		{
			$binaryOperators[] = $po->createParser($key);
		}

		$binaryOperatorColumnData = new Loco\LazyAltParser(
			$binaryOperators);

		/**
		 *
		 * @todo Operator with restricted operand types
		 *       glob
		 *       match
		 *       regexp
		 */

		$binaryOperation = new Loco\ConcParser(
			[
				'expression',
				'binary-operator-literal',
				'expression'
			],
			function ($left, $o, $right) {
				return new BinaryOperation($o, $left, $right);
			});

		$inOperation = new Loco\ConcParser(
			[
				'expression',
				'space',
				self::negatableKeywordParser('in'),
				'whitespace',
				new Loco\StringParser('('),
				'whitespace',
				'expression-list',
				'whitespace',
				new Loco\StringParser(')')
			],
			function ($left, $_s1, $include, $_s2, $_po, $_s3, $members) {
				$instance = new MemberOf($left, $members);
				$instance->toggle($include);
				return $instance;
			});

		$between = new Loco\ConcParser(
			[
				'expression',
				'space',
				self::negatableKeywordParser('between'),
				'space',
				'expression',
				'space',
				self::keywordParser('and'),
				'space',
				'expression'
			],
			function ($left, $s1, $between, $s2, $min, $s3, $nd, $s4,
				$max) {
				$x = new Between($left, $min, $max);
				$x->toggle($between);
				return $x;
			});

		$literal = new Loco\LazyAltParser(
			[
				'timestamp',
				'number',
				'string',
				'true',
				'false',
				'null'
			]);

		$this->grammar = new Loco\Grammar('complex-expression',
			[
				'complex-expression' => new Loco\LazyAltParser(
					[
						'between',
						'in-operation',
						'binary-operation',
						'unary-operation',
						'case',
						'expression'
					]),
				'expression' => new Loco\LazyAltParser(
					[
						'function',
						'parenthesis',
						'parameter',
						'literal',
						'structure-path'
					]),
				'function' => $procedure,
				'comma-expression' => $commaExpression,
				'comma-expression-list' => $commaExpressionList,
				'expression-list' => $expressionList,
				'parameter' => $parameterName,
				'structure-path' => $path,
				'parenthesis' => $parenthesis,
				'unary-operation' => $unaryOperation,
				'in-operation' => $inOperation,
				'binary-operation' => $binaryOperation,
				'between' => $between,
				'identifier' => $identifier,
				'function-name' => $functionName,
				'when-then' => $whenThen,
				'when-then-star' => $moreWhenThen,
				'case' => $case,
				'literal' => $literal,
				'timestamp' => $timestamp,
				'string' => $string,
				'number' => $number,
				'true' => self::keywordParser('true',
					function () {
						return new Data(true, K::DATATYPE_BOOLEAN);
					}),
				'false' => self::keywordParser('false',
					function () {
						return new Data(false, K::DATATYPE_BOOLEAN);
					}),
				'null' => self::keywordParser('null',
					function () {
						return new Data(null, K::DATATYPE_NULL);
					}),
				'unary-operator-literal' => $unaryOperatorColumnData,
				'binary-operator-literal' => $binaryOperatorColumnData,
				'whitespace' => $whitespace,
				'space' => $space
			]); // grammar

		$this->evaluatorFlags |= self::GRAMMAR_BUILT;
	}

	/**
	 * Indicates Grammar is built
	 *
	 * @var integer
	 */
	const GRAMMAR_BUILT = 0x01;

	// Patterns
	const PATTERN_IDENTIFIER = 'identifier';

	const PATTERN_FUNCTION_NAME = 'function';

	const PATTERN_PARAMETER_NAME = 'parameter';

	const PATTERN_SPACE = 'space';

	const PATTERN_WHITESPACE = 'whitespace';

	const PATTERN_NUMBER = 'number';

	const DATETIME_FORMAT = 'Y-m-d\TH:i:s.uO';

	/**
	 *
	 * @var Loco\Grammar Syntax grammar
	 */
	private $grammar;

	/**
	 *
	 * @var integer
	 */
	private $evaluatorFlags;

	/**
	 *
	 * @var array of PolishNotationOperation
	 */
	private $operators;
}

class PolishNotationOperation
{

	const PRE_WHITESPACE = Bitset::BIT_01;

	const PRE_SPACE = self::PRE_WHITESPACE | Bitset::BIT_02;

	// (0x20 + 0x10);
	const POST_WHITESPACE = Bitset::BIT_03;

	const POST_SPACE = self::POST_WHITESPACE | Bitset::BIT_04;

	// (0x02 + 0x01);
	const WHITESPACE = self::PRE_WHITESPACE | self::POST_WHITESPACE;

	const SPACE = self::PRE_SPACE | self::POST_SPACE;

	const KEYWORD = Bitset::BIT_05;

	public $operator;

	public $className;

	public $flags;

	public function __construct($key, $flags, $className)
	{
		$this->operator = $key;
		$this->flags = $flags;
		$this->className = $className;
	}

	public function createParser($key)
	{
		$parsers = [];
		if ($this->flags & self::KEYWORD)
			$parsers[] = Evaluator::keywordParser($key, $this);
		else
			$parsers[] = new Loco\StringParser($key, $this);

		if ($this->flags & self::PRE_SPACE)
		{
			array_unshift($parsers,
				(($this->flags & self::PRE_SPACE) == self::PRE_SPACE) ? 'space' : 'whitespace');
		}

		if ($this->flags & self::POST_SPACE)
		{
			array_push($parsers,
				(($this->flags & self::POST_SPACE) == self::POST_SPACE) ? 'space' : 'whitespace');
		}

		return new Loco\ConcParser($parsers, $this);
	}

	public function __invoke()
	{
		$flags = $this->flags;
		if ($flags & self::KEYWORD)
		{
			if ($flags & self::PRE_SPACE)
				$flags |= self::PRE_SPACE;
			if ($flags & self::POST_SPACE)
				$flags |= self::POST_SPACE;
		}
		$s = '';
		if (($flags & self::PRE_SPACE) == self::PRE_SPACE)
			$s .= ' ';
		$s .= $this->operator;
		if (($flags & self::POST_SPACE) == self::POST_SPACE)
			$s .= ' ';
		return $s;
	}
}

final class BinaryPolishNotationOperation extends PolishNotationOperation
{

	public function __construct($key,
		$flags = PolishNotationOperation::WHITESPACE, $className = null)
	{
		parent::__construct($key,
			($flags | PolishNotationOperation::WHITESPACE),
			($className ? $className : BinaryOperation::class));
	}
}

final class UnaryPolishNotationOperation extends PolishNotationOperation
{

	public function __construct($key,
		$flags = PolishNotationOperation::POST_WHITESPACE,
		$className = null)
	{
		parent::__construct($key,
			($flags | PolishNotationOperation::POST_WHITESPACE),
			($className ? $className : UnaryOperation::class));
	}
}