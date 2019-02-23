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

	public $name;

	public $arguments;

	public function __construct($name, $arguments = array())
	{
		$this->name = $name;
		$this->arguments = $arguments;
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

class ExpressionParser
{

	public function __construct($patterns = array())
	{
		$rx = array (
				'identifier' => '[a-zA-Z_@#]+',
				'function' => '[a-zA-Z_][a-zA-Z0-9_]*',
				'parameter' => '[a-zA-Z0-9_]+',
				'whitespace' => '[ \n\r\t]*' 
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
		
		$literal = new Loco\LazyAltParser(array (
				new Loco\ConcParser(array (
						'quote',
						'literal-value',
						'quote' 
				), function ($a, $v, $q)
				{
					return $v;
				}),
				new Loco\ConcParser(array (
						'double-quote',
						'literal-value',
						'double-quote' 
				), function ($a, $v, $q)
				{
					return $v;
				}),
				'literal-value' 
		));
		
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
				)),
				'expression',
				new Loco\EmptyParser() 
		));
		
		$functionCall = new Loco\ConcParser(array (
				'function-name',
				'whitespace',
				new Loco\StringParser('('),
				'whitespace',
				'expression-list',
				'whitespace',
				new Loco\StringParser(')') 
		), function ()
		{
			$args = func_get_arg(4);
			if ($args instanceof Expression)
				$args = array (
						$args 
				);
			elseif (!\is_array($args))
				$args = array ();
			return new FunctionExpression(func_get_arg(0), $args);
		});
		
		$this->grammar = new Loco\Grammar('expression', array (
				'expression' => new Loco\LazyAltParser(array (
						'parameter',
						'structure-path',
						'function'
				)),
				'comma-expression' =>$commaExpression,
				'comma-expression-list' => $commaExpressionList,
				'expression-list' => $expressionList,
				'parameter' => $parameterName,
				'identifier' => $identifier,
				'structure-path' => $path,
				'function-name' => $functionName,
				'function' => $functionCall,
				'whitespace' => $whitespace
		)); // grammar
	}
	
	public function __invoke($string)
	{
		return $this->parse($string);
	}
	
	public function parse ($string) {
		return $this->grammar->parse($string);
	}

	private $grammar;
}