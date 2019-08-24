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

class ExpressionEvaluator
{

	/**
	 * Create ColumnExpression
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
	 * @param mixed $value Literal value
	 * @param integer|TableColumnStructure $type Data type hint
	 *       
	 * @return \NoreSources\SQL\LiteralExpression
	 */
	public static function literal($value, $type = K::DATATYPE_UNDEFINED)
	{
		if ($type instanceof TableColumnStructure)
		{
			$type = $type->getProperty(K::PROPERTY_COLUMN_DATA_TYPE);
		}

		return new LiteralExpression($value, $type);
	}

	/**
	 * @param string $name Parameter name
	 *       
	 * @return \NoreSources\SQL\ParameterExpression
	 */
	public static function parameter($name)
	{
		return new ParameterExpression($name);
	}

	/**
	 * Create a preformatted expression
	 * @param mixed $value
	 * @return \NoreSources\SQL\PreformattedExpression
	 */
	public static function pre($value)
	{
		return new PreformattedExpression($value);
	}

	// Patterns
	const PATTERN_IDENTIFIER = 'identifier';
	const PATTERN_FUNCTION_NAME = 'function';
	const PATTERN_PARAMETER_NAME = 'parameter';
	const PATTERN_SPACE = 'space';
	const PATTERN_WHITESPACE = 'whitespace';
	const PATTERN_NUMBER = 'number';
	const DATETIME_FORMAT = 'Y-m-d\TH:i:s.uO';

	public function __construct($patterns = array())
	{
		$this->builderFlags = 0;
		$this->patterns = array ();
		$this->operators = array (
				1 => array (
						'not' => new UnaryPolishNotationOperation('NOT', PolishNotationOperation::KEYWORD | PolishNotationOperation::POST_SPACE),
						'!' => new UnaryPolishNotationOperation('NOT', PolishNotationOperation::KEYWORD),
						'-' => new UnaryPolishNotationOperation('-'),
						'~' => new UnaryPolishNotationOperation('~')
				),
				2 => array (
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
						'and' => new BinaryPolishNotationOperation('AND', PolishNotationOperation::KEYWORD | PolishNotationOperation::SPACE),
						'or' => new BinaryPolishNotationOperation('OR', PolishNotationOperation::KEYWORD | PolishNotationOperation::SPACE)
				)
		);
	}

	/**
	 * Evalute expression description
	 * @param string|mixed $string
	 * @return \NoreSources\SQL\Expression|NULL|mixed
	 */
	public function __invoke($expression)
	{
		return $this->evaluate($expression);
	}

	// bm3
	/**
	 * @param string|array $expression
	 */
	public function evaluate($expression)
	{
		if (\is_object($expression))
		{
			if ($expression instanceof Expression)
			{
				return $expression;
			}
			elseif ($expression instanceof \DateTime)
			{
				return new LiteralExpression($expression, K::DATATYPE_TIMESTAMP);
			}
		}
		elseif (\is_null($expression))
		{
			return new LiteralExpression($expression, K::DATATYPE_NULL);
		}
		elseif (\is_bool($expression))
		{
			return new LiteralExpression($expression, K::DATATYPE_BOOLEAN);
		}
		elseif (\is_numeric($expression))
		{
			return new LiteralExpression($expression, is_float($expression) ? K::DATATYPE_FLOAT : K::DATATYPE_INTEGER);
		}
		elseif (is_string($expression))
		{
			return $this->evaluateString($expression);
		}
		elseif (ns\ArrayUtil::isArray($expression))
		{
			if (ns\ArrayUtil::isAssociative($expression))
			{
				if (\count($expression) == 1)
				{
					reset($expression);
					list ( $a, $b ) = each($expression);
					if (!\is_array($b))
					{
						return new BinaryOperatorExpression('=', $this->evaluate($a), $this->evaluate($b));
					}
				}

				return $this->evaluatePolishNotation($expression);
			}
			else
			{
				return array_map(array (
						$this,
						'evaluate'
				), $expression);
			}
		}

		$t = is_object($expression) ? get_class($expression) : gettype($expression);
		throw new ExpressionEvaluationException('Invalid argument type/class ' . $t);
	}

	public static function keywordParser($keyword, $callable = null)
	{
		return new Loco\LazyAltParser(array (
				new Loco\StringParser(strtolower($keyword)),
				new Loco\StringParser(strtoupper($keyword))
		), $callable);
	}

	/**
	 * Evaluate a polish notation form expression
	 * @param string $key Lower case operator or function name
	 * @param array $operands
	 */
	protected function evaluatePolishNotationElement($key, $operands)
	{
		$length = strlen($key);
		if (strpos($key, '()') == ($length - 2))
		{
			return new FunctionExpression(substr($key, 0, $length - 2), $operands);
		}

		$c = count($operands);
		$o = false;
		if (\array_key_exists($c, $this->operators))
			$o = ns\ArrayUtil::keyValue($this->operators[$c], $key, false);

		if (!($o instanceof PolishNotationOperation))
			$o = ns\ArrayUtil::keyValue($this->operators['*'], $key, false);

		if (!($o instanceof PolishNotationOperation))
			throw new ExpressionEvaluationException('Unable to evalate Polish notation ' . $key . ' => ');

		$cls = new \ReflectionClass($o->className);
		return $cls->newInstanceArgs(array_merge(array (
				$o->operator
		), $operands));
	}

	/**
	 * @param array $polishTree
	 */
	private function evaluatePolishNotation($polishTree)
	{
		$result = null;
		foreach ($polishTree as $key => $operands)
		{
			$key = strtolower($key);
			$operands = array_map(array (
					$this,
					'evaluate'
			), $operands);

			$expression = $this->evaluatePolishNotationElement($key, $operands);

			if (!($expression instanceof Expression))
			{
				throw new ExpressionEvaluationException('Unable to create expression (got ' . var_export($expression, true) . ')');
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
	 * @param string $string
	 * @return mixed
	 */
	private function evaluateString($string)
	{
		if (!($this->builderFlags & self::GRAMMAR_BUILT))
		{
			$this->buildGrammar();
		}

		return $this->grammar->parse($string);
	}

	/**
	 * Override default PCRE patterns
	 * @param string $key
	 * @param string $pattern
	 */
	public function setPattern($key, $pattern)
	{
		$this->patterns[$key] = $pattern;
		$this->builderFlags &= ~self::GRAMMAR_BUILT;
	}

	private function buildGrammar()
	{
		$rx = array (
				self::PATTERN_IDENTIFIER => '[a-zA-Z_@#][a-zA-Z0-9_@#]*',
				self::PATTERN_FUNCTION_NAME => '[a-zA-Z_][a-zA-Z0-9_]*',
				self::PATTERN_PARAMETER_NAME => '[a-zA-Z0-9_]+',
				self::PATTERN_SPACE => '[ \n\r\t]+',
				self::PATTERN_WHITESPACE => '[ \n\r\t]*',
				self::PATTERN_NUMBER => '-?(0|[1-9][0-9]*)(\.[0-9]+)?([eE][-+]?[0-9]+)?'
		);

		foreach ($this->patterns as $key => $pattern)
		{
			$rx[$key] = $pattern;
		}

		$any = new Loco\Utf8Parser();
		$whitespace = new Loco\RegexParser(chr(1) . '^(' . $rx[self::PATTERN_WHITESPACE] . ')' . chr(1), function ()
		{
			return '';
		});

		$space = new Loco\RegexParser(chr(1) . '^(' . $rx[self::PATTERN_SPACE] . ')' . chr(1), function ()
		{
			return '';
		});

		$parameterName = new Loco\RegexParser(chr(1) . '^:(' . $rx[self::PATTERN_PARAMETER_NAME] . ')' . chr(1), function ($full, $name)
		{
			return new ParameterExpression($name);
		});
		$functionName = new Loco\RegexParser(chr(1) . '^(' . $rx[self::PATTERN_FUNCTION_NAME] . ')' . chr(1));
		$identifier = new Loco\RegexParser(chr(1) . '^(' . $rx[self::PATTERN_IDENTIFIER] . ')' . chr(1), function ($all, $name)
		{
			return $name;
		});

		$subpath = new Loco\ConcParser(array (
				new Loco\StringParser('.'),
				'identifier'
		), function ($dot, $identifier)
		{
			return $dot . $identifier;
		});

		$path = new Loco\LazyAltParser(array (
				new Loco\ConcParser(array (
						'identifier',
						new Loco\GreedyMultiParser($subpath, 1, 3, function ()
						{
							return implode('', func_get_args());
						})
				), function ($a, $b)
				{
					return $a . $b;
				}),
				'identifier'
		), function ($p)
		{
			return new ColumnExpression($p);
		});

		$commaExpression = new Loco\ConcParser(array (
				new Loco\StringParser(','),
				'whitespace',
				'expression'
		), function ($c, $w, $expression)
		{
			return $expression;
		});

		$commaExpressionList = new Loco\GreedyMultiParser('comma-expression', 1, null);

		$expressionList = new Loco\LazyAltParser(array (
				new Loco\ConcParser(array (
						'expression',
						'comma-expression-list'
				), function ($first, $others)
				{
					$a = array (
							$first
					);
					return array_merge($a, $others);
				}),
				'expression',
				new Loco\EmptyParser()
		), function ($a)
		{
			if (\is_null($a))
				return array ();
			elseif ($a instanceof Expression)
				return array (
						$a
				);
			return $a;
		});

		$call = new Loco\ConcParser(array (
				'function-name',
				'whitespace',
				new Loco\StringParser('('),
				'whitespace',
				'expression-list',
				'whitespace',
				new Loco\StringParser(')')
		), function ($name, $w1, $s1, $w2, $args)
		{
			return new FunctionExpression($name, $args);
		});

		$parenthesis = new Loco\ConcParser(array (
				new Loco\StringParser('('),
				'whitespace',
				'expression',
				'whitespace',
				new Loco\StringParser(')')
		), function ()
		{
			return new ParenthesisExpression(func_get_arg(2));
		});

		$whenThen = new Loco\ConcParser(array (
				self::keywordParser('when'),
				'space',
				'expression',
				'space',
				self::keywordParser('then'),
				'space',
				'expression'
		), function ()
		{
			return new CaseOptionExpression(func_get_arg(2), func_get_arg(6));
		});

		$moreWhenThen = new Loco\GreedyMultiParser(new Loco\ConcParser(array (
				'space',
				'when-then'
		), function ()
		{
			return func_get_arg(1);
		}), 0, null);

		$else = new Loco\ConcParser(array (
				'space',
				self::keywordParser('else'),
				'space',
				'expression'
		), function ()
		{
			return func_get_arg(3);
		});

		$case = new Loco\ConcParser(array (
				self::keywordParser('case'),
				'space',
				'expression',
				'space',
				'when-then',
				'when-then-star',
				new Loco\GreedyMultiParser($else, 0, 1, function ()
				{
					$n = func_num_args();
					return ($n > 0) ? func_get_arg(0) : null;
				})
		), function ()
		{
			$c = new CaseExpression(func_get_arg(2));
			$c->options->append(func_get_arg(4));
			foreach (func_get_arg(5) as $wt)
			{
				$c->options->append($wt);
			}
			$c->else = func_get_arg(6);
			return $c;
		});

		$stringContent = new Loco\GreedyStarParser(new Loco\LazyAltParser(array (
				new Loco\Utf8Parser(array (
						"'"
				)),
				new Loco\StringParser("''", function ()
				{
					return "'";
				})
		)), function ()
		{
			return implode('', func_get_args());
		});

		$string = new Loco\ConcParser(array (
				new Loco\StringParser("'"),
				$stringContent,
				new Loco\StringParser("'")
		), function ()
		{
			return new LiteralExpression(func_get_arg(1), K::DATATYPE_STRING);
		});

		// Date & Time

		$year = new Loco\RegexParser(chr(1) . '^(\+|-)?[0-9]{1,4}' . chr(1));
		$month = new Loco\LazyAltParser(array (
				new Loco\RegexParser(chr(1) . '^1[0-2]' . chr(1)),
				new Loco\RegexParser(chr(1) . '^0[0-9]' . chr(1))
		));
		$day = new Loco\LazyAltParser(array (
				new Loco\RegexParser(chr(1) . '^3[0-1]' . chr(1)),
				new Loco\RegexParser(chr(1) . '^[0-2][0-9]' . chr(1))
		));
		$hour = new Loco\LazyAltParser(array (
				new Loco\RegexParser(chr(1) . '^2[0-3]' . chr(1)),
				new Loco\RegexParser(chr(1) . '^[0-1][0-9]' . chr(1))
		));

		$minutes = new Loco\RegexParser(chr(1) . '^[0-5][0-9]' . chr(1));

		$seconds = new Loco\LazyAltParser(array (
				new Loco\ConcParser(array (
						$minutes,
						new Loco\StringParser('.'),
						new Loco\RegexParser(chr(1) . '^[0-1][0-9][0-9]' . chr(1))
				), function ($m, $_d, $s)
				{
					return array (
							$m,
							$s
					);
				}),
				$minutes
		));

		$baseDate = new Loco\ConcParser(array (
				$year,
				$month,
				$day
		));
		$extendedDate = new Loco\ConcParser(array (
				$year,
				new Loco\StringParser('-'),
				$month,
				new Loco\StringParser('-'),
				$day
		), function ($y, $_1, $m, $_2, $d)
		{
			return array (
					$y,
					$m,
					$d
			);
		});

		$dateSeparator = new Loco\StringParser('-');
		$optionalDateSeparator = new Loco\LazyAltParser(array (
				$dateSeparator,
				new Loco\EmptyParser()
		));
		$date = new Loco\LazyAltParser(array (
				new Loco\ConcParser(array (
						$year,
						$optionalDateSeparator,
						$month,
						$optionalDateSeparator,
						$day
				), function ($y, $a, $m, $b, $d)
				{
					return array (
							'year' => $y,
							'month' => $m,
							'day' => $d
					);
				}),
				// YYYY-MM
				new Loco\ConcParser(array (
						$year,
						$dateSeparator,
						$month
				), function ($y, $a, $m)
				{
					return array (
							'year' => $y,
							'month' => $m,
							'day' => date('d')
					);
				}),
				// --MM-DD
				new Loco\ConcParser(array (
						new Loco\StringParser('--'),
						$month,
						$optionalDateSeparator,
						$day
				), function ($a, $m, $b, $d)
				{
					return array (
							'year' => date('y'),
							'month' => $m,
							'day' => $d
					);
				})
		));

		$optionalTimeSeparator = new Loco\LazyAltParser(array (
				new Loco\StringParser(':'),
				new Loco\EmptyParser()
		));

		// .sss
		$optionalTimeFraction = new Loco\LazyAltParser(array (
				new Loco\ConcParser(array (
						new Loco\LazyAltParser(array (
								new Loco\StringParser('.'),
								new Loco\StringParser(',')
						)),
						new Loco\RegexParser(chr(1) . '^[0-9]+' . chr(1))
				), function ($s, $f)
				{
					$length = strlen($f);
					return intval($f) * pow(10, -$length);
				}),
				new Loco\EmptyParser(function ()
				{
					return 0;
				})
		));

		$time = new Loco\LazyAltParser(array (
				// hh:mm:ss[.sss]
				new Loco\ConcParser(array (
						$hour,
						$optionalTimeSeparator,
						$minutes,
						$optionalTimeSeparator,
						$seconds,
						$optionalTimeFraction
				), function ($h, $a, $m, $b, $s, $f)
				{
					return array (
							'hour' => $h,
							'minute' => $m,
							'second' => $s,
							'microsecond' => $f * 1000000
					);
				}),
				// hh:mm
				new Loco\ConcParser(array (
						$hour,
						$optionalTimeSeparator,
						$minutes
				), function ($h, $a, $m)
				{
					return array (
							'hour' => $h,
							'minute' => $m,
							'second' => '00',
							'microsecond' => 0
					);
				}),
				// hh
				new Loco\ConcParser(array (
						$hour
				), function ($h)
				{
					return array (
							'hour' => $h,
							'minute' => '00',
							'second' => '00',
							'microsecond' => 0
					);
				})
		));

		$timezone = new Loco\LazyAltParser(array (
				// Z (UTC)
				new Loco\StringParser('Z', function ()
				{
					return array (
							'timezone' => '+0000'
					);
				}),
				// Hour & minutes
				new Loco\ConcParser(array (
						new Loco\RegexParser(chr(1) . '^\+|-' . chr(1)),
						$hour,
						$optionalTimeSeparator,
						$minutes
				), function ($s, $h, $_1, $m)
				{
					return array (
							'timezone' => $s . $h . $m
					);
				}),
				// hour
				new Loco\ConcParser(array (
						new Loco\RegexParser(chr(1) . '^\+|-' . chr(1)),
						$hour
				), function ($s, $h)
				{
					return array (
							'timezone' => $s . $h . '00'
					);
				})
		));

		$dateTimeSeparator = new Loco\LazyAltParser(array (
				new Loco\StringParser('T'),
				$space
		));

		$dateTime = new Loco\LazyAltParser(array (
				new Loco\ConcParser(array (
						$date,
						$dateTimeSeparator,
						$time,
						$timezone
				), function ($d, $s, $t, $z)
				{
					return array_merge($d, $t, $z);
				}),
				new Loco\ConcParser(array (
						$date,
						$dateTimeSeparator,
						$time
				), function ($d, $s, $t)
				{
					return array_merge($d, $t);
				}),
				$date,
				new Loco\ConcParser(array (
						$time,
						$timezone
				), function ($t, $z)
				{
					return array_merge($t, $z);
				}),
				$time
		));

		$timestamp = new Loco\ConcParser(array (
				new Loco\StringParser('#'),
				$dateTime,
				new Loco\StringParser('#')
		), function ($a, $dt, $b)
		{
			$timezone = date('O');
			$date = date('Y-m-d');
			$time = date('H:i:s.u');

			if (ns\ArrayUtil::keyExists($dt, 'hour'))
			{
				$time = $dt['hour'] . ':' . $dt['minute'] . ':' . $dt['second'] . '.' . $dt['microsecond'];
			}

			if (ns\ArrayUtil::keyExists($dt, 'year'))
			{
				$date = $dt['year'] . '-' . $dt['month'] . '-' . $dt['day'];
			}

			if (ns\ArrayUtil::keyExists($dt, 'timezone'))
			{
				$timezone = $dt['timezone'];
			}

			$dateTimeString = $date . 'T' . $time . $timezone;
			$dateTime = \DateTime::createFromFormat(self::DATETIME_FORMAT, $dateTimeString);

			return new LiteralExpression($dateTime, K::DATATYPE_TIMESTAMP);
		});

		$number = new Loco\RegexParser(chr(1) . '^(' . $rx[self::PATTERN_NUMBER] . ')' . chr(1), function ($full, $v)
		{
			if (strpos($v, '.') >= 0)
			{
				$t = K::DATATYPE_FLOAT;
				$v = floatval($v);
			}
			else
			{
				$v = intval($v);
				$t = K::DATATYPE_INTEGER;
			}

			return new LiteralExpression($v, $t);
		});

		$unaryOperators = array ();
		foreach ($this->operators[1] as $key => $po)
		{
			$unaryOperators[] = $po->createParser($key);
		}

		$unaryOperatorLiteral = new Loco\LazyAltParser($unaryOperators);

		$unaryOperation = new Loco\ConcParser(array (
				'unary-operator-literal',
				'expression'
		), function ($o, $operand)
		{
			return new UnaryOperatorExpression(strtolower($o), $operand);
		});

		$binaryOperators = array ();
		foreach ($this->operators[2] as $key => $po)
		{
			$binaryOperators[] = $po->createParser($key);
		}

		$binaryOperatorLiteral = new Loco\LazyAltParser($binaryOperators);

		/**
		 * @todo Operator with restricted operand types
		 *       like
		 *       glob
		 *       match
		 *       regexp
		 */

		$binaryOperation = new Loco\ConcParser(array (
				'expression',
				'binary-operator-literal',
				'expression'
		), function ($left, $o, $right)
		{
			return new BinaryOperatorExpression($o, $left, $right);
		});

		$literal = new Loco\LazyAltParser(array (
				'timestamp',
				'number',
				'string',
				'true',
				'false'
		));

		$this->grammar = new Loco\Grammar('complex-expression', array (
				'complex-expression' => new Loco\LazyAltParser(array (
						'binary-operation',
						'unary-operation',
						'parenthesis',
						'function',
						'case',
						'expression'
				)),
				'expression' => new Loco\LazyAltParser(array (
						'parameter',
						'literal',
						'structure-path'
				)),
				'function' => $call,
				'comma-expression' => $commaExpression,
				'comma-expression-list' => $commaExpressionList,
				'expression-list' => $expressionList,
				'parameter' => $parameterName,
				'structure-path' => $path,
				'parenthesis' => $parenthesis,
				'unary-operation' => $unaryOperation,
				'binary-operation' => $binaryOperation,
				'identifier' => $identifier,
				'function-name' => $functionName,
				'when-then' => $whenThen,
				'when-then-star' => $moreWhenThen,
				'case' => $case,
				'literal' => $literal,
				'timestamp' => $timestamp,
				'string' => $string,
				'number' => $number,
				'true' => self::keywordParser('true', function ()
				{
					return new LiteralExpression(true, K::DATATYPE_BOOLEAN);
				}),
				'false' => self::keywordParser('false', function ()
				{
					return new LiteralExpression(false, K::DATATYPE_BOOLEAN);
				}),
				'unary-operator-literal' => $unaryOperatorLiteral,
				'binary-operator-literal' => $binaryOperatorLiteral,
				'whitespace' => $whitespace,
				'space' => $space
		)); // grammar

		$this->builderFlags |= self::GRAMMAR_BUILT;
	}

	/**
	 * @param string $key Lower-case operator name
	 * @param string $sql SQL operator
	 * @param integer|'*' $operandCount Number of operands
	 * @param string $className Expression class
	 *       
	 * @throws \InvalidArgumentException
	 */
	protected function setOperator($key, $sql, $operandCount, $className = null)
	{
		if (!\is_string($className))
		{
			switch ($operandCount)
			{
				case 1:
					$className = UnaryOperatorExpression::class;
					break;
				case 2:
					$className = BinaryOperatorExpression::class;
					break;
			}
		}
		
		if (!is_subclass_of($className, Expression::class, true))
			throw new \InvalidArgumentException('Invalid class name ' . strval($className));

		if (!ns\ArrayUtil::keyExists($this->operators, $operandCount))
			$this->operators[$operandCount] = array ();

		$this->operators[$operandCount][$key] = new PolishNotationOperation($sql, $className);
	}
	
	const GRAMMAR_BUILT = 0x01;

	private $grammar;

	private $patterns;

	private $builderFlags;

	private $operators;
}

class PolishNotationOperation
{
	const PRE_WHITESPACE = 0x10;
	const PRE_SPACE = 0x30; // (0x20 + 0x10);
	const POST_WHITESPACE = 0x01;
	const POST_SPACE = 0x03; // (0x02 + 0x01);
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
			throw new \Exception(self::class . ' is a private class of ' . ExpressionEvaluator::class . ' (not allowed in ' . $context . ' context)');
		}
		
		$this->operator = $key;
		$this->flags = $flags;
		$this->className = $className;
	}
	
	public function createParser($key)
	{
		$parsers = array ();
		if ($this->flags & self::KEYWORD)
			$parsers[] = ExpressionEvaluator::keywordParser($key, $this);
			else
				$parsers[] = new Loco\StringParser($key, $this);
				
				if ($this->flags & self::PRE_SPACE)
				{
					array_unshift($parsers, (($this->flags & self::PRE_SPACE) == self::PRE_SPACE) ? 'space' : 'whitespace');
				}
				
				if ($this->flags & self::POST_SPACE)
				{
					array_push($parsers, (($this->flags & self::POST_SPACE) == self::POST_SPACE) ? 'space' : 'whitespace');
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