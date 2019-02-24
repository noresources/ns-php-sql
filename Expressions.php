<?php

namespace NoreSources\SQL;

use Ferno\Loco as Loco;
use Ferno\Loco\Utf8Parser;

interface Expression
{

	function build(StatementBuilder $builder, StructureResolver $resolver);
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

	function build(StatementBuilder $builder, StructureResolver $resolver)
	{
		return $builder->getLiteral($this->value, $this->type);
	}
}

class ParameterExpression implements Expression
{

	public $name;

	public function __construct($name)
	{
		$this->name = $name;
	}

	function build(StatementBuilder $builder, StructureResolver $resolver)
	{
		return $builder->getParameter($this->name);
	}
}

class ColumnExpression implements Expression
{

	public $path;

	public function __construct($path)
	{
		$this->path = $path;
	}

	function build(StatementBuilder $builder, StructureResolver $resolver)
	{
		return $builder->getCanonicalName($this->path, $resolver);
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
	 *
	 * @var \ArrayObject
	 */
	public $arguments;

	public function __construct($name, $arguments = array())
	{
		$this->name = $name;
		if (\NoreSources\ArrayUtil::isArray($arguments))
		{
			$this->arguments = new \ArrayObject(\NoreSources\ArrayUtil::createArray($arguments));
		}
		else
			$this->arguments = new \ArrayObject();
	}

	function build(StatementBuilder $builder, StructureResolver $resolver)
	{
		/**
		 *
		 * @todo function factory
		 */
		return $this->name . '()';
	}
}

class ParenthesisExpression
{

	/**
	 *
	 * @var Expression
	 */
	public $expression;

	public function __construct(Expression $expression)
	{
		$this->expression;
	}

	function build(StatementBuilder $builder, StructureResolver $resolver)
	{
		return '(' . $this->expression->build($builder, $resolver) . ')';
	}
}

class UnaryOperatorExpression
{

	/**
	 *
	 * @var string
	 */
	public $operator;

	/**
	 *
	 * @var Expression
	 */
	public $operand;

	public function __construct($operator, Expression $operand)
	{
		$this->operand = $operator;
		$this->operand = $operand;
	}

	function build(StatementBuilder $builder, StructureResolver $resolver)
	{
		return $this->operator . ' ' . $this->operand->build($builder, $resolver);
	}
}

class CaseOptionExpression
{

	/**
	 *
	 * @var Expression
	 */
	public $when;

	/**
	 *
	 * @var Expression
	 */
	public $then;

	public function __construct(Expression $when, Expression $then)
	{
		$this->when = $when;
		$this->then = $then;
	}

	function build(StatementBuilder $builder, StructureResolver $resolver)
	{
		return 'WHEN ' . $this->when->build($builder, $resolver) . ' THEN ' . $this->then->build($builder, $resolver);
	}
}

class CaseExpression
{

	/**
	 *
	 * @var Expression
	 */
	public $subject;

	/**
	 *
	 * @var \ArrayObject
	 */
	public $options;

	/**
	 *
	 * @var Expression
	 */
	public $else;

	public function __construct(Expression $subject)
	{
		$this->subject = $subject;
		$this->options = new \ArrayObject();
		$this->else = null;
	}

	function build(StatementBuilder $builder, StructureResolver $resolver)
	{
		$s = 'CASE ' . $this->subject;
		foreach ($this->options as $option)
		{
			$s .= ' ' . $option->build($builder, $resolver);
		}
		
		if ($this->else instanceof Expression)
		{
			$s .= ' ELSE ' . $this->else->build($builder, $resolver);
		}
		
		return $s;
	}
}

class ExpressionParser
{

	public function __construct($patterns = array())
	{
		$rx = array (
				'identifier' => '[a-zA-Z_@#]+',
				'function' => '[a-zA-Z_][a-zA-Z0-9_]*',
				'parameter' => '[a-zA-Z0-9_]+',
				'space' => '[ \n\r\t]+',
				'whitespace' => '[ \n\r\t]*',
				'number' => '-?(0|[1-9][0-9]*)(\.[0-9]+)?([eE][-+]?[0-9]+)?' 
		);
		
		foreach ($patterns as $key => $pattern)
		{
			$rx[$key] = $pattern;
		}
		
		$any = new Loco\Utf8Parser();
		$whitespace = new Loco\RegexParser(chr(1) . '^(' . $rx['whitespace'] . ')' . chr(1), function ()
		{
			return '';
		});
		
		$space = new Loco\RegexParser(chr(1) . '^(' . $rx['space'] . ')' . chr(1), function ()
		{
			return '';
		});
		
		$parameterName = new Loco\RegexParser(chr(1) . '^:(' . $rx['parameter'] . ')' . chr(1), function ($full, $name)
		{
			return new ParameterExpression($name);
		});
		$functionName = new Loco\RegexParser(chr(1) . '^(' . $rx['function'] . ')' . chr(1));
		$identifier = new Loco\RegexParser(chr(1) . '^\.(' . $rx['identifier'] . ')' . chr(1), function ($all, $name)
		{
			return $name;
		});
		
		$path = new Loco\GreedyMultiParser('identifier', 1, 3, function ()
		{
			return new ColumnExpression(implode('.', func_get_args()));
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
					$a = array($first);
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
		
		$number = new Loco\RegexParser(chr(1) . '^(' . $rx['number'] . ')' . chr(1), function ($full, $v)
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
		
		$unaryOperator = new Loco\ConcParser(array (
				'operator',
				'whitespace',
				'expression' 
		), function ()
		{
			return new UnaryOperatorExpression(func_get_arg(0), func_get_arg(2));
		});
		
		$literal = new Loco\LazyAltParser(array (
				'number',
				'string',
				'true',
				'false' 
		));
		
		$this->grammar = new Loco\Grammar('expression', array (
				'expression' => new Loco\LazyAltParser(array (
						'parameter',
						'structure-path',
						'case',
						'parenthesis',
						'function',
						'literal' 
				)),
				'function' => $call,
				'comma-expression' => $commaExpression,
				'comma-expression-list' => $commaExpressionList,
				'expression-list' => $expressionList,
				'parameter' => $parameterName,
				'structure-path' => $path,
				'parenthesis' => $parenthesis,
				//'unary-operator' => $unaryOperator,
				'identifier' => $identifier,
				'function-name' => $functionName,
				'when-then' => $whenThen,
				'when-then-star' => $moreWhenThen,
				'case' => $case,
				'literal' => $literal,
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
				'whitespace' => $whitespace,
				'space' => $space 
		)); // grammar
	}

	public function __invoke($string)
	{
		return $this->parse($string);
	}

	public function parse($string)
	{
		return $this->grammar->parse($string);
	}

	private static function keywordParser($keyword, $callable = null)
	{
		return new Loco\LazyAltParser(array (
				new Loco\StringParser(strtolower($keyword)),
				new Loco\StringParser(strtoupper($keyword)) 
		), $callable);
	}

	private $grammar;
}