<?php

namespace NoreSources\SQL;

use NoreSources as ns;
use Ferno\Loco as Loco;

interface Expression
{

	/**
	 * @param StatementBuilder $builder
	 * @param StructureResolver $resolver
	 * @return string
	 */
	function buildExpression(StatementBuilder $builder, StructureResolver $resolver);

	/**
	 * @return integer
	 */
	function getExpressionDataType();
}

class PreformattedExpression implements Expression
{

	public $expression;

	/**
	 * @param mixed $value
	 * @param integer $type
	 */
	public function __construct($value, $type = K::kDataTypeUndefined)
	{
		$this->expression = $value;
		$this->type = $type;
	}

	function buildExpression(StatementBuilder $builder, StructureResolver $resolver)
	{
		return $this->expression;
	}

	function getExpressionDataType()
	{
		return $this->type;
	}

	private $type;
}

class LiteralExpression implements Expression
{

	public $value;

	public $type;

	public function __construct($value, $type = K::kDataTypeString)
	{
		$this->value = $value;
		$this->type = $type;
	}

	function buildExpression(StatementBuilder $builder, StructureResolver $resolver)
	{
		return $builder->getLiteral($this);
	}

	function getExpressionDataType()
	{
		return $this->type;
	}
}

class ParameterExpression implements Expression
{

	public $name;

	public function __construct($name)
	{
		$this->name = $name;
	}

	function buildExpression(StatementBuilder $builder, StructureResolver $resolver)
	{
		return $builder->getParameter($this->name);
	}

	function getExpressionDataType()
	{
		return K::kDataTypeUndefined;
	}
}

/**
 * Table column path or Result column aliasden
 */
class ColumnExpression implements Expression
{

	/**
	 * @var string
	 */
	public $path;

	public function __construct($path)
	{
		$this->path = $path;
	}

	function buildExpression(StatementBuilder $builder, StructureResolver $resolver)
	{
		$target = $resolver->findColumn($this->path);

		if ($target instanceof TableColumnStructure)
		{
			$parts = explode('.', $this->path);
			foreach ($parts as $part)
			{
				if ($resolver->isAlias($part))
					return $builder->escapeIdentifierPath($parts);
			}

			return $builder->getCanonicalName($target);
		}
		else
			return $builder->escapeIdentifier($this->path);
	}

	/**
	 * Column data type will be resolved by StructureResolver
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\Expression::getExpressionDataType()
	 */
	function getExpressionDataType()
	{
		return K::kDataTypeUndefined;
	}
}

class TableExpression implements Expression
{

	/**
	 * @var string
	 */
	public $path;

	public function __construct($path)
	{
		$this->path = $path;
	}

	function buildExpression(StatementBuilder $builder, StructureResolver $resolver)
	{
		$target = $resolver->findTable($this->path);

		if ($target instanceof TableStructure)
		{
			$parts = explode('.', $this->path);
			foreach ($parts as $part)
			{
				if ($resolver->isAlias($part))
					return $builder->escapeIdentifierPath($parts);
			}

			return $builder->getCanonicalName($target);
		}
		else
			return $builder->escapeIdentifier($this->path);
	}

	function getExpressionDataType()
	{
		return K::kDataTypeUndefined;
	}
}

class FunctionExpression implements Expression
{

	/**
	 * Function name
	 * @var string
	 */
	public $name;

	/**
	 * @var \ArrayObject
	 */
	public $arguments;

	/**
	 * Function return type
	 * @var integer
	 */
	public $returnType;

	public function __construct($name, $arguments = array())
	{
		$this->name = $name;
		$this->returnType = K::kDataTypeUndefined;

		/**
		 * @todo Recognize function and get its return type
		 */

		if (ns\ArrayUtil::isArray($arguments))
		{
			$this->arguments = new \ArrayObject(ns\ArrayUtil::createArray($arguments));
		}
		else
		{
			$this->arguments = new \ArrayObject();
		}
	}

	function buildExpression(StatementBuilder $builder, StructureResolver $resolver)
	{
		/**
		 * @todo builder function translator
		 */
		$s = $this->name . '(';
		$a = array ();
		foreach ($this->arguments as $arg)
		{
			$o[] = $arg->buildExpression($builder, $resolver);
		}
		return $s . implode(', ', $o) . ')';
	}

	function getExpressionDataType()
	{
		return $this->returnType;
	}
}

class ListExpression extends \ArrayObject implements Expression
{

	public $separator;

	public function __construct($list = array(), $separator = ', ')
	{
		parent::__construct($list);
		$this->separator = $separator;
	}

	public function buildExpression(StatementBuilder $builder, StructureResolver $resolver)
	{
		$s = '';
		$first = true;
		foreach ($this as $expression)
		{
			if (!$first)
				$s .= $this->separator;
			$s .= $expression->buildExpression($builder, $resolver);
		}

		return $s;
	}

	function getExpressionDataType()
	{
		$set = false;
		$current = K::kDataTypeUndefined;

		foreach ($this as $expression)
		{
			$t = $expression->getExpressionDataType();
			if ($set && ($t != $current))
			{
				return K::kDataTypeUndefined;
			}

			$set = true;
			$current = $t;
		}

		return $current;
	}
}

class ParenthesisExpression implements Expression
{

	/**
	 * @var Expression
	 */
	public $expression;

	public function __construct(Expression $expression)
	{
		$this->expression;
	}

	function buildExpression(StatementBuilder $builder, StructureResolver $resolver)
	{
		return '(' . $this->expression->buildExpression($builder, $resolver) . ')';
	}

	function getExpressionDataType()
	{
		return $this->expression->getExpressionDataType();
	}
}

class UnaryOperatorExpression implements Expression
{

	/**
	 * @var string
	 */
	public $operator;

	/**
	 * @var Expression
	 */
	public $operand;

	public $type;

	public function __construct($operator, Expression $operand, $type = K::kDataTypeUndefined)
	{
		$this->operator = $operator;
		$this->operand = $operand;
		$this->type = $type;
	}

	function buildExpression(StatementBuilder $builder, StructureResolver $resolver)
	{
		return $this->operator . ' ' . $this->operand->buildExpression($builder, $resolver);
	}

	function getExpressionDataType()
	{
		if ($this->type == K::kDataTypeUndefined)
			return $this->operand->getExpressionDataType();
		return $this->type;
	}
}

class BinaryOperatorExpression implements Expression
{

	public $operator;

	/**
	 * @var Expression
	 */
	public $leftOperand;

	/**
	 * @var Expression
	 */
	public $rightOperand;

	public $type;

	/**
	 * @param string $operator
	 * @param Expression $left
	 * @param Expression $right
	 */
	public function __construct($operator, Expression $left = null, Expression $right = null, $type = K::kDataTypeUndefined)
	{
		$this->operator = $operator;
		$this->leftOperand = $left;
		$this->rightOperand = $right;
		$this->type = $type;
	}

	function buildExpression(StatementBuilder $builder, StructureResolver $resolver)
	{
		return $this->leftOperand->buildExpression($builder, $resolver) . ' ' . $this->operator . ' ' . $this->rightOperand->buildExpression($builder, $resolver);
	}

	function getExpressionDataType()
	{
		$t = $this->type;
		if ($t == K::kDataTypeUndefined)
			$t = $this->leftOperand->getExpressionDataType();
		if ($t == K::kDataTypeUndefined)
			$t = $this->rightOperand->getExpressionDataType();

		return $t;
	}
}

class CaseOptionExpression
{

	/**
	 * @var Expression
	 */
	public $when;

	/**
	 * @var Expression
	 */
	public $then;

	public function __construct(Expression $when, Expression $then)
	{
		$this->when = $when;
		$this->then = $then;
	}

	function buildExpression(StatementBuilder $builder, StructureResolver $resolver)
	{
		return 'WHEN ' . $this->when->buildExpression($builder, $resolver) . ' THEN ' . $this->then->buildExpression($builder, $resolver);
	}

	function getExpressionDataType()
	{
		return $this->then->getExpressionDataType();
	}
}

class CaseExpression implements Expression
{

	/**
	 * @var Expression
	 */
	public $subject;

	/**
	 * @var \ArrayObject
	 */
	public $options;

	/**
	 * @var Expression
	 */
	public $else;

	public function __construct(Expression $subject)
	{
		$this->subject = $subject;
		$this->options = new \ArrayObject();
		$this->else = null;
	}

	function buildExpression(StatementBuilder $builder, StructureResolver $resolver)
	{
		$s = 'CASE ' . $this->subject;
		foreach ($this->options as $option)
		{
			$s .= ' ' . $option->buildExpression($builder, $resolver);
		}

		if ($this->else instanceof Expression)
		{
			$s .= ' ELSE ' . $this->else->buildExpression($builder, $resolver);
		}

		return $s;
	}

	function getExpressionDataType()
	{
		$set = false;
		$current = K::kDataTypeUndefined;

		foreach ($this->options as $option)
		{
			$t = $option->getExpressionDataType();
			if ($set && ($t != $current))
			{
				return K::kDataTypeUndefined;
			}

			$set = true;
			$current = $t;
		}

		if ($this->else instanceof Expression)
		{
			$t = $this->else->getExpressionDataType();
			if ($set && ($t != $current))
			{
				return K::kDataTypeUndefined;
			}

			$current = $t;
		}

		return $current;
	}
}

class ExpressionBuilder
{
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
	}

	public function __invoke($string)
	{
		return $this->evaluate($string);
	}

	/**
	 * @param string|array $expression
	 */
	public function evaluate($expression)
	{
		if (is_string($expression))
			return $this->evaluateString($expression);
		else if (ns\ArrayUtil::isArray($expression))
			return $this->evaluatePolishNotation($expression);

		throw new \InvalidArgumentException('Invalid argument type ' . gettype($expression));
	}

	/**
	 * @param array $polishTree
	 */
	public function evaluatePolishNotation($polishTree)
	{}

	/**
	 * @param string $string
	 * @return mixed
	 */
	public function evaluateString($string)
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
			return new LiteralExpression(func_get_arg(1), K::kDataTypeString);
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

			return new LiteralExpression($dateTime, K::kDataTypeTimestamp);
		});

		$number = new Loco\RegexParser(chr(1) . '^(' . $rx[self::PATTERN_NUMBER] . ')' . chr(1), function ($full, $v)
		{
			if (strpos($v, '.') >= 0)
			{
				$t = K::kDataTypeDecimal;
				$v = floatval($v);
			}
			else
			{
				$v = intval($v);
				$t = K::kDataTypeInteger;
			}

			return new LiteralExpression($v, $t);
		});

		$unaryOperatorLiteral = new Loco\LazyAltParser(array (
				new Loco\StringParser('-'),
				new Loco\StringParser('+'),
				new Loco\StringParser('~')
		));

		$unaryOperation = new Loco\ConcParser(array (
				'unary-operator-literal',
				'whitespace',
				'expression'
		), function ($o, $w1, $operand)
		{
			return new UnaryOperatorExpression(strtolower($o), $operand);
		});

		$binaryOperatorLiteral = new Loco\LazyAltParser(array (
				new Loco\StringParser('||'),
				new Loco\StringParser('*'),
				new Loco\StringParser('/'),
				new Loco\StringParser('%'),
				new Loco\StringParser('+'),
				new Loco\StringParser('<<'),
				new Loco\StringParser('>>'),
				new Loco\StringParser('&'),
				new Loco\StringParser('|'),
				new Loco\StringParser('<'),
				new Loco\StringParser('<='),
				new Loco\StringParser('>'),
				new Loco\StringParser('>='),
				new Loco\StringParser('='),
				new Loco\StringParser('==', function ()
				{
					return '=';
				}),
				new Loco\StringParser('<>'),
				new Loco\StringParser('!=', function ()
				{
					return '<>';
				})
		));

		$spaceBinaryOperatorLiteral = new Loco\LazyAltParser(array (
				self::keywordParser('is not'),
				self::keywordParser('is'),
				self::keywordParser('and'),
				self::keywordParser('or')
		));

		/**
		 * @todo Operator with restricted operand types
		 *       like
		 *       glob
		 *       match
		 *       regexp
		 */

		$binaryOperation = new Loco\ConcParser(array (
				'expression',
				'whitespace',
				'binary-operator-literal',
				'whitespace',
				'expression'
		), function ($left, $w1, $o, $w2, $right)
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
						'binary-operator',
						'unary-operator',
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
				'unary-operator' => $unaryOperation,
				'binary-operator' => $binaryOperation,
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
					return new LiteralExpression(true, K::kDataTypeBoolean);
				}),
				'false' => self::keywordParser('false', function ()
				{
					return new LiteralExpression(false, K::kDataTypeBoolean);
				}),
				'unary-operator-literal' => $unaryOperatorLiteral,
				'binary-operator-literal' => $binaryOperatorLiteral,
				'whitespace' => $whitespace,
				'space' => $space
		)); // grammar

		$this->builderFlags |= self::GRAMMAR_BUILT;
	}

	private static function keywordParser($keyword, $callable = null)
	{
		return new Loco\LazyAltParser(array (
				new Loco\StringParser(strtolower($keyword)),
				new Loco\StringParser(strtoupper($keyword)) 
		), $callable);
	}
	const GRAMMAR_BUILT = 0x01;

	private $grammar;

	private $patterns;

	private $builderFlags;
}