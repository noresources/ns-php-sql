<?php
namespace NoreSources\SQL;

use NoreSources as ns;
use Ferno\Loco as Loco;
use NoreSources\SQL\Constants as K;

class ExpressionEvaluationException extends \ErrorException
{

	public function __construct($message)
	{
		parent::__construct($message);
	}
}

/**
 * Any type that can be evaluated by the ExpressionEvaluator
 */
class Evaluable
{

	public $value;

	public function __construct($value)
	{
		$this->value = $value;
	}
}

class ExpressionEvaluator
{

	/**
	 * Create ColumnExpression
	 *
	 * @param string|TableColumnStructure $column
	 * @return ColumnExpression
	 */
	public static function column($column)
	{
		if ($column instanceof TableColumnStructure)
		{
			$column = $column->getPath();
		}

		return new ColumnExpression($column);
	}

	/**
	 * Create a LiteralExpression
	 *
	 * @param mixed $value
	 *        	Literal value
	 * @param integer|TableColumnStructure $type
	 *        	Data type hint
	 *        	
	 * @return \NoreSources\SQL\LiteralExpression
	 */
	public static function literal($value, $type = K::DATATYPE_UNDEFINED)
	{
		if ($type instanceof TableColumnStructure)
		{
			$type = $type->getProperty(K::COLUMN_PROPERTY_DATA_TYPE);
		}

		return new LiteralExpression($value, $type);
	}

	/**
	 *
	 * @param string $name
	 *        	Parameter name
	 *        	
	 * @return \NoreSources\SQL\ParameterExpression
	 */
	public static function parameter($name)
	{
		return new ParameterExpression($name);
	}

	public function __construct()
	{
		$this->evaluatorFlags = 0;
		$this->operators = [
				1 => [
						'not' => new UnaryPolishNotationOperation('NOT', PolishNotationOperation::KEYWORD |
						PolishNotationOperation::POST_SPACE),
						'!' => new UnaryPolishNotationOperation('NOT', PolishNotationOperation::KEYWORD),
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
						'and' => new BinaryPolishNotationOperation('AND', PolishNotationOperation::KEYWORD |
						PolishNotationOperation::SPACE),
						'or' => new BinaryPolishNotationOperation('OR', PolishNotationOperation::KEYWORD |
						PolishNotationOperation::SPACE)
				]
		];
	}

	/**
	 * Evalute expression description
	 *
	 * @param string|mixed $string
	 * @return \NoreSources\SQL\Expression|NULL|mixed
	 */
	public function __invoke($evaluable)
	{
		return $this->evaluate($evaluable);
	}

	/**
	 *
	 * @method Expression evaluate ($evaluable)
	 *        
	 * @param string $name
	 * @param array $args
	 *
	 * @return Expression
	 *
	 * @throws \BadMethodCallException
	 */
	public function __call($name, $args)
	{
		if ($name == 'evaluate')
		{
			return call_user_func_array(array(
				$this,
				'evaluateEvaluable'
			), $args);
		}

		throw new \BadMethodCallException($name . ' is not a valid method name');
	}

	/**
	 *
	 * @method Expression evaluate ($evaluable)
	 *        
	 * @param string $name
	 * @param array $args
	 *
	 * @return Expression
	 *
	 * @throws \BadMethodCallException
	 */
	public static function __callStatic($name, $args)
	{
		if ($name == 'evaluate')
		{
			if (!(self::$instance instanceof ExpressionEvaluator))
			{
				self::$instance = new ExpressionEvaluator();
			}

			return call_user_func_array(array(
				self::$instance,
				'evaluateEvaluable'
			), $args);
		}

		throw new \BadMethodCallException($name . ' is not a valid method name');
	}

	// bm3
	/**
	 *
	 * @param string|array $evaluable
	 */
	public function evaluateEvaluable($evaluable)
	{
		if ($evaluable instanceof Evaluable)
		{
			$evaluable = $evaluable->value;
		}

		if (\is_object($evaluable))
		{
			if ($evaluable instanceof Expression)
			{
				return $evaluable;
			}
			elseif ($evaluable instanceof \DateTime)
			{
				return new LiteralExpression($evaluable, K::DATATYPE_TIMESTAMP);
			}
		}
		elseif (\is_null($evaluable))
		{
			return new LiteralExpression($evaluable, K::DATATYPE_NULL);
		}
		elseif (\is_bool($evaluable))
		{
			return new LiteralExpression($evaluable, K::DATATYPE_BOOLEAN);
		}
		elseif (is_int($evaluable))
		{
			return new LiteralExpression($evaluable, K::DATATYPE_INTEGER);
		}
		elseif (is_float($evaluable))
		{
			return new LiteralExpression($evaluable, K::DATATYPE_FLOAT);
		}
		elseif (\is_numeric($evaluable))
		{
			$i = \intval($evaluable);
			$f = \floatval($evaluable);
			if ($i == $f)
				return new LiteralExpression($i, K::DATATYPE_INTEGER);
			else
				return new LiteralExpression($f, K::DATATYPE_FLOAT);
		}
		elseif (is_string($evaluable))
		{
			return $this->evaluateString($evaluable);
		}
		elseif (ns\Container::isArray($evaluable))
		{
			if (ns\Container::isAssociative($evaluable))
			{
				if (\count($evaluable) == 1)
				{
					reset($evaluable);
					list ($a, $b) = each($evaluable);
					if (!\is_array($b))
					{
						return new BinaryOperatorExpression('=', $this->evaluate($a),
							$this->evaluate($b));
					}
				}

				return $this->evaluatePolishNotation($evaluable);
			}
			else
			{
				return array_map(array(
					$this,
					'evaluateEvaluable'
				), $expression);
			}
		}

		$t = is_object($evaluable) ? get_class($evaluable) : gettype($evaluable);
		throw new ExpressionEvaluationException('Invalid argument type/class ' . $t);
	}

	/**
	 * Private method
	 *
	 * @param string $keyword
	 * @param boolean $positiveCallable
	 * @param boolean $negativeCallable
	 * @return \Ferno\Loco\LazyAltParser
	 */
	public static function negatableKeywordParser($keyword, $positiveCallable = true,
		$negativeCallable = false)
	{
		$nc = $negativeCallable;
		if (!\is_callable($negativeCallable))
		{
			$nc = function () use ($negativeCallable) {
				return $negativeCallable;
			};
		}

		return new Loco\LazyAltParser(
			array(
				new Loco\ConcParser(
					array(
						self::keywordParser('not'),
						'space',
						self::keywordParser($keyword)
					), $nc),
				self::keywordParser($keyword, $positiveCallable)
			));
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
			array(
				new Loco\StringParser(strtolower($keyword)),
				new Loco\StringParser(strtoupper($keyword))
			), $c);
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
		$length = strlen($key);
		if (strpos($key, '()') == ($length - 2))
		{
			return new FunctionExpression(substr($key, 0, $length - 2), $operands);
		}

		$c = count($operands);
		$o = false;
		if (\array_key_exists($c, $this->operators))
			$o = ns\Container::keyValue($this->operators[$c], $key, false);

		if (!($o instanceof PolishNotationOperation))
			$o = ns\Container::keyValue($this->operators['*'], $key, false);

		if (!($o instanceof PolishNotationOperation))
			throw new ExpressionEvaluationException(
				'Unable to evalate Polish notation ' . $key . ' => ');

		$cls = new \ReflectionClass($o->className);
		return $cls->newInstanceArgs(array_merge(array(
			$o->operator
		), $operands));
	}

	/**
	 *
	 * @param array $polishTree
	 */
	private function evaluatePolishNotation($polishTree)
	{
		$result = null;
		foreach ($polishTree as $key => $operands)
		{
			$key = strtolower($key);
			$operands = array_map(array(
				$this,
				'evaluateEvaluable'
			), $operands);

			$expression = $this->evaluatePolishNotationElement($key, $operands);

			if (!($expression instanceof Expression))
			{
				throw new ExpressionEvaluationException(
					'Unable to create expression (got ' . var_export($expression, true) . ')');
			}

			if ($result instanceof Expression)
			{
				$result = new BinaryOperatorExpression('AND', $result, $expression);
			}
			else
				$result = $expression;
		}

		if (!($result instanceof Expression))
		{
			throw new ExpressionEvaluationException('Unable to create expression');
		}

		return $result;
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
			throw new ExpressionEvaluationException($e->getMessage());
		}
	}

	private function buildGrammar()
	{
		$rx = array(
			self::PATTERN_IDENTIFIER => '[a-zA-Z_@#][a-zA-Z0-9_@#]*',
			self::PATTERN_FUNCTION_NAME => '[a-zA-Z_][a-zA-Z0-9_]*',
			self::PATTERN_PARAMETER_NAME => '[a-zA-Z0-9_]+',
			self::PATTERN_SPACE => '[ \n\r\t]+',
			self::PATTERN_WHITESPACE => '[ \n\r\t]*',
			self::PATTERN_NUMBER => '-?(0|[1-9][0-9]*)(\.[0-9]+)?([eE][-+]?[0-9]+)?'
		);

		$any = new Loco\Utf8Parser();
		$whitespace = new Loco\RegexParser(
			chr(1) . '^(' . $rx[self::PATTERN_WHITESPACE] . ')' . chr(1), function () {
				return '';
			});

		$space = new Loco\RegexParser(chr(1) . '^(' . $rx[self::PATTERN_SPACE] . ')' . chr(1),
			function () {
				return '';
			});

		$parameterName = new Loco\RegexParser(
			chr(1) . '^:(' . $rx[self::PATTERN_PARAMETER_NAME] . ')' . chr(1),
			function ($full, $name) {
				return new ParameterExpression($name);
			});
		$functionName = new Loco\RegexParser(
			chr(1) . '^(' . $rx[self::PATTERN_FUNCTION_NAME] . ')' . chr(1));
		$identifier = new Loco\RegexParser(
			chr(1) . '^(' . $rx[self::PATTERN_IDENTIFIER] . ')' . chr(1),
			function ($all, $name) {
				return $name;
			});

		$subpath = new Loco\ConcParser(array(
			new Loco\StringParser('.'),
			'identifier'
		), function ($dot, $identifier) {
			return $dot . $identifier;
		});

		$path = new Loco\LazyAltParser(
			array(
				new Loco\ConcParser(
					array(
						'identifier',
						new Loco\GreedyMultiParser($subpath, 1, 3,
							function () {
								return implode('', func_get_args());
							})
					), function ($a, $b) {
						return $a . $b;
					}),
				'identifier'
			), function ($p) {
				return new ColumnExpression($p);
			});

		$commaExpression = new Loco\ConcParser(
			array(
				new Loco\StringParser(','),
				'whitespace',
				'expression'
			), function ($c, $w, $expression) {
				return $expression;
			});

		$commaExpressionList = new Loco\GreedyMultiParser('comma-expression', 1, null);

		$expressionList = new Loco\LazyAltParser(
			array(
				new Loco\ConcParser(array(
					'expression',
					'comma-expression-list'
				),
					function ($first, $others) {
						$a = array(
							$first
						);
						return array_merge($a, $others);
					}),
				'expression',
				new Loco\EmptyParser()
			),
			function ($a) {
				if (\is_null($a))
					return array();
				elseif ($a instanceof Expression)
					return array(
						$a
					);
				return $a;
			});

		$procedure = new Loco\ConcParser(
			array(
				'function-name',
				'whitespace',
				new Loco\StringParser('('),
				'whitespace',
				'expression-list',
				'whitespace',
				new Loco\StringParser(')')
			),
			function ($name, $w1, $s1, $w2, $args) {
				return new FunctionExpression($name, $args);
			});

		$parenthesis = new Loco\ConcParser(
			array(
				new Loco\StringParser('('),
				'whitespace',
				'complex-expression',
				'whitespace',
				new Loco\StringParser(')')
			), function () {
				return new ParenthesisExpression(func_get_arg(2));
			});

		$whenThen = new Loco\ConcParser(
			array(
				self::keywordParser('when'),
				'space',
				'expression',
				'space',
				self::keywordParser('then'),
				'space',
				'expression'
			), function () {
				return new CaseOptionExpression(func_get_arg(2), func_get_arg(6));
			});

		$moreWhenThen = new Loco\GreedyMultiParser(
			new Loco\ConcParser(array(
				'space',
				'when-then'
			), function () {
				return func_get_arg(1);
			}), 0, null);

		$else = new Loco\ConcParser(
			array(
				'space',
				self::keywordParser('else'),
				'space',
				'expression'
			), function () {
				return func_get_arg(3);
			});

		$case = new Loco\ConcParser(
			array(
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
			),
			function () {
				$c = new CaseExpression(func_get_arg(2));
				$c->options->append(func_get_arg(4));
				foreach (func_get_arg(5) as $wt)
				{
					$c->options->append($wt);
				}
				$c->else = func_get_arg(6);
				return $c;
			});

		$stringContent = new Loco\GreedyStarParser(
			new Loco\LazyAltParser(
				array(
					new Loco\Utf8Parser(array(
						"'"
					)),
					new Loco\StringParser("''", function () {
						return "'";
					})
				)), function () {
				return implode('', func_get_args());
			});

		$string = new Loco\ConcParser(
			array(
				new Loco\StringParser("'"),
				$stringContent,
				new Loco\StringParser("'")
			), function () {
				return new LiteralExpression(func_get_arg(1), K::DATATYPE_STRING);
			});

		// Date & Time

		$year = new Loco\RegexParser(chr(1) . '^(\+|-)?[0-9]{1,4}' . chr(1));
		$month = new Loco\LazyAltParser(
			array(
				new Loco\RegexParser(chr(1) . '^1[0-2]' . chr(1)),
				new Loco\RegexParser(chr(1) . '^0[0-9]' . chr(1))
			));
		$day = new Loco\LazyAltParser(
			array(
				new Loco\RegexParser(chr(1) . '^3[0-1]' . chr(1)),
				new Loco\RegexParser(chr(1) . '^[0-2][0-9]' . chr(1))
			));
		$hour = new Loco\LazyAltParser(
			array(
				new Loco\RegexParser(chr(1) . '^2[0-3]' . chr(1)),
				new Loco\RegexParser(chr(1) . '^[0-1][0-9]' . chr(1))
			));

		$minutes = new Loco\RegexParser(chr(1) . '^[0-5][0-9]' . chr(1));

		$seconds = new Loco\LazyAltParser(
			array(
				new Loco\ConcParser(
					array(
						$minutes,
						new Loco\StringParser('.'),
						new Loco\RegexParser(chr(1) . '^[0-1][0-9][0-9]' . chr(1))
					), function ($m, $_d, $s) {
						return array(
							$m,
							$s
						);
					}),
				$minutes
			));

		$baseDate = new Loco\ConcParser(array(
			$year,
			$month,
			$day
		));
		$extendedDate = new Loco\ConcParser(
			array(
				$year,
				new Loco\StringParser('-'),
				$month,
				new Loco\StringParser('-'),
				$day
			), function ($y, $_1, $m, $_2, $d) {
				return array(
					$y,
					$m,
					$d
				);
			});

		$dateSeparator = new Loco\StringParser('-');
		$optionalDateSeparator = new Loco\LazyAltParser(
			array(
				$dateSeparator,
				new Loco\EmptyParser()
			));
		$date = new Loco\LazyAltParser(
			array(
				new Loco\ConcParser(
					array(
						$year,
						$optionalDateSeparator,
						$month,
						$optionalDateSeparator,
						$day
					),
					function ($y, $a, $m, $b, $d) {
						return array(
							'year' => $y,
							'month' => $m,
							'day' => $d
						);
					}),
				// YYYY-MM
				new Loco\ConcParser(array(
					$year,
					$dateSeparator,
					$month
				),
					function ($y, $a, $m) {
						return array(
							'year' => $y,
							'month' => $m,
							'day' => date('d')
						);
					}),
				// --MM-DD
				new Loco\ConcParser(
					array(
						new Loco\StringParser('--'),
						$month,
						$optionalDateSeparator,
						$day
					),
					function ($a, $m, $b, $d) {
						return array(
							'year' => date('y'),
							'month' => $m,
							'day' => $d
						);
					})
			));

		$optionalTimeSeparator = new Loco\LazyAltParser(
			array(
				new Loco\StringParser(':'),
				new Loco\EmptyParser()
			));

		// .sss
		$optionalTimeFraction = new Loco\LazyAltParser(
			array(
				new Loco\ConcParser(
					array(
						new Loco\LazyAltParser(
							array(
								new Loco\StringParser('.'),
								new Loco\StringParser(',')
							)),
						new Loco\RegexParser(chr(1) . '^[0-9]+' . chr(1))
					),
					function ($s, $f) {
						$length = strlen($f);
						return intval($f) * pow(10, -$length);
					}),
				new Loco\EmptyParser(function () {
					return 0;
				})
			));

		$time = new Loco\LazyAltParser(
			array(
				// hh:mm:ss[.sss]
				new Loco\ConcParser(
					array(
						$hour,
						$optionalTimeSeparator,
						$minutes,
						$optionalTimeSeparator,
						$seconds,
						$optionalTimeFraction
					),
					function ($h, $a, $m, $b, $s, $f) {
						return array(
							'hour' => $h,
							'minute' => $m,
							'second' => $s,
							'microsecond' => $f * 1000000
						);
					}),
				// hh:mm
				new Loco\ConcParser(array(
					$hour,
					$optionalTimeSeparator,
					$minutes
				),
					function ($h, $a, $m) {
						return array(
							'hour' => $h,
							'minute' => $m,
							'second' => '00',
							'microsecond' => 0
						);
					}),
				// hh
				new Loco\ConcParser(array(
					$hour
				),
					function ($h) {
						return array(
							'hour' => $h,
							'minute' => '00',
							'second' => '00',
							'microsecond' => 0
						);
					})
			));

		$timezone = new Loco\LazyAltParser(
			array(
				// Z (UTC)
				new Loco\StringParser('Z', function () {
					return array(
						'timezone' => '+0000'
					);
				}),
				// Hour & minutes
				new Loco\ConcParser(
					array(
						new Loco\RegexParser(chr(1) . '^\+|-' . chr(1)),
						$hour,
						$optionalTimeSeparator,
						$minutes
					), function ($s, $h, $_1, $m) {
						return array(
							'timezone' => $s . $h . $m
						);
					}),
				// hour
				new Loco\ConcParser(
					array(
						new Loco\RegexParser(chr(1) . '^\+|-' . chr(1)),
						$hour
					), function ($s, $h) {
						return array(
							'timezone' => $s . $h . '00'
						);
					})
			));

		$dateTimeSeparator = new Loco\LazyAltParser(array(
			new Loco\StringParser('T'),
			$space
		));

		$dateTime = new Loco\LazyAltParser(
			array(
				new Loco\ConcParser(array(
					$date,
					$dateTimeSeparator,
					$time,
					$timezone
				), function ($d, $s, $t, $z) {
					return array_merge($d, $t, $z);
				}),
				new Loco\ConcParser(array(
					$date,
					$dateTimeSeparator,
					$time
				), function ($d, $s, $t) {
					return array_merge($d, $t);
				}),
				$date,
				new Loco\ConcParser(array(
					$time,
					$timezone
				), function ($t, $z) {
					return array_merge($t, $z);
				}),
				$time
			));

		$timestamp = new Loco\ConcParser(
			array(
				new Loco\StringParser('#'),
				$dateTime,
				new Loco\StringParser('#')
			),
			function ($a, $dt, $b) {
				$timezone = date('O');
				$date = date('Y-m-d');
				$time = date('H:i:s.u');

				if (ns\Container::keyExists($dt, 'hour'))
				{
					$time = $dt['hour'] . ':' . $dt['minute'] . ':' . $dt['second'] . '.' .
					$dt['microsecond'];
				}

				if (ns\Container::keyExists($dt, 'year'))
				{
					$date = $dt['year'] . '-' . $dt['month'] . '-' . $dt['day'];
				}

				if (ns\Container::keyExists($dt, 'timezone'))
				{
					$timezone = $dt['timezone'];
				}

				$dateTimeString = $date . 'T' . $time . $timezone;
				$dateTime = \DateTime::createFromFormat(self::DATETIME_FORMAT, $dateTimeString);

				return new LiteralExpression($dateTime, K::DATATYPE_TIMESTAMP);
			});

		$number = new Loco\RegexParser(chr(1) . '^(' . $rx[self::PATTERN_NUMBER] . ')' . chr(1),
			function ($full, $v) {
				$i = \intval($v);
				$f = \floatval($v);
				if ($i == $v)
					return new LiteralExpression($i, K::DATATYPE_INTEGER);
				else
					return new LiteralExpression($f, K::DATATYPE_FLOAT);
			});

		$unaryOperators = array();
		foreach ($this->operators[1] as $key => $po)
		{
			$unaryOperators[] = $po->createParser($key);
		}

		$unaryOperatorLiteral = new Loco\LazyAltParser($unaryOperators);

		$unaryOperation = new Loco\ConcParser(array(
			'unary-operator-literal',
			'expression'
		), function ($o, $operand) {
			return new UnaryOperatorExpression(strtolower($o), $operand);
		});

		$binaryOperators = array();
		foreach ($this->operators[2] as $key => $po)
		{
			$binaryOperators[] = $po->createParser($key);
		}

		$binaryOperatorLiteral = new Loco\LazyAltParser($binaryOperators);

		/**
		 *
		 * @todo Operator with restricted operand types
		 *       like
		 *       glob
		 *       match
		 *       regexp
		 */

		$binaryOperation = new Loco\ConcParser(
			array(
				'expression',
				'binary-operator-literal',
				'expression'
			),
			function ($left, $o, $right) {
				return new BinaryOperatorExpression($o, $left, $right);
			});

		$likeOperation = new Loco\ConcParser(
			array(
				'expression',
				'space',
				self::negatableKeywordParser('like', 'LIKE', 'NOT LIKE'),
				'space',
				'string'
			),
			function ($e, $_s1, $o, $_s2, $s) {
				return new BinaryOperatorExpression($o, $e, $s);
			});

		$inOperation = new Loco\ConcParser(
			array(
				'expression',
				'space',
				self::negatableKeywordParser('in'),
				'whitespace',
				new Loco\StringParser('('),
				'whitespace',
				'expression-list',
				'whitespace',
				new Loco\StringParser(')')
			),
			function ($left, $_s1, $include, $_s2, $_po, $_s3, $right) {
				return new InOperatorExpression($left, $right, $include);
			});

		$between = new Loco\ConcParser(
			array(
				'expression',
				'space',
				self::negatableKeywordParser('between'),
				'space',
				'expression',
				'space',
				self::keywordParser('and'),
				'space',
				'expression'
			),
			function ($left, $s1, $between, $s2, $min, $s3, $nd, $s4, $max) {
				$x = new BetweenExpression($left, $min, $max);
				$x->inside = $between;
				return $x;
			});

		$literal = new Loco\LazyAltParser(
			array(
				'timestamp',
				'number',
				'string',
				'true',
				'false',
				'null'
			));

		$this->grammar = new Loco\Grammar('complex-expression',
			array(
				'complex-expression' => new Loco\LazyAltParser(
					array(
						'between',
						'in-operation',
						'like-operation',
						'binary-operation',
						'unary-operation',
						'case',
						'expression'
					)),
				'expression' => new Loco\LazyAltParser(
					array(
						'function',
						'parenthesis',
						'parameter',
						'literal',
						'structure-path'
					)),
				'function' => $procedure,
				'comma-expression' => $commaExpression,
				'comma-expression-list' => $commaExpressionList,
				'expression-list' => $expressionList,
				'parameter' => $parameterName,
				'structure-path' => $path,
				'parenthesis' => $parenthesis,
				'unary-operation' => $unaryOperation,
				'in-operation' => $inOperation,
				'like-operation' => $likeOperation,
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
						return new LiteralExpression(true, K::DATATYPE_BOOLEAN);
					}),
				'false' => self::keywordParser('false',
					function () {
						return new LiteralExpression(false, K::DATATYPE_BOOLEAN);
					}),
				'null' => self::keywordParser('null',
					function () {
						return new LiteralExpression(null, K::DATATYPE_NULL);
					}),
				'unary-operator-literal' => $unaryOperatorLiteral,
				'binary-operator-literal' => $binaryOperatorLiteral,
				'whitespace' => $whitespace,
				'space' => $space
			)); // grammar

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

	private static $instance;
}

class PolishNotationOperation
{

	const PRE_WHITESPACE = 0x10;

	const PRE_SPACE = 0x30;

	// (0x20 + 0x10);
	const POST_WHITESPACE = 0x01;

	const POST_SPACE = 0x03;

	// (0x02 + 0x01);
	const WHITESPACE = 0x11;

	const SPACE = 0x33;

	const KEYWORD = 0x04;

	public $operator;

	public $className;

	public $flags;

	public function __construct($key, $flags, $className)
	{
		$builder = debug_backtrace();
		$context = null;
		if (count($builder) >= 2 && isset($builder[1]['class']))
		{
			$context = $builder[1]['class'];
		}
		
		if (!($context && ($context == ExpressionEvaluator::class || is_subclass_of($context, self::class, true))))
		{
			$context = ($context ? $context : 'global');
			throw new ExpressionEvaluationException(self::class . ' is a private class of ' . ExpressionEvaluator::class . ' (not allowed in ' . $context . ' context)');
		}

		$this->operator = $key;
		$this->flags = $flags;
		$this->className = $className;
	}

	public function createParser($key)
	{
		$parsers = array();
		if ($this->flags & self::KEYWORD)
			$parsers[] = ExpressionEvaluator::keywordParser($key, $this);
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

class BinaryPolishNotationOperation extends PolishNotationOperation
{

	public function __construct($key, $flags = PolishNotationOperation::WHITESPACE, $className = null)
	{
		parent::__construct($key, ($flags | PolishNotationOperation::WHITESPACE), ($className ? $className : BinaryOperatorExpression::class));
	}
}

class UnaryPolishNotationOperation extends PolishNotationOperation
{

	public function __construct($key, $flags = PolishNotationOperation::POST_WHITESPACE, $className = null)
	{
		parent::__construct($key, ($flags | PolishNotationOperation::POST_WHITESPACE), ($className ? $className : UnaryOperatorExpression::class));
	}
}