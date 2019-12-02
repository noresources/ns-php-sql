<?php
namespace NoreSources\SQL;

use NoreSources as ns;
use Ferno\Loco as Loco;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\ExpressionEvaluator as X;

/**
 * Function
 */
class FunctionExpression implements Expression
{

	/**
	 * Function name
	 *
	 * @var string
	 */
	public $name;

	/**
	 *
	 * @var ListExpression
	 */
	public $arguments;

	/**
	 * Function return type
	 *
	 * @var integer
	 */
	public $returnType;

	public function __construct($name, $arguments = [])
	{
		$this->name = $name;
		$this->returnType = K::DATATYPE_UNDEFINED;
		$this->arguments = new ListExpression($arguments);
	}

	public function tokenize(TokenStream &$stream, StatementContext $context)
	{
		return $stream->keyword($this->name)
			->text('(')
			->expression($this->arguments, $context)
			->text(')');
	}

	public function getExpressionDataType()
	{
		return $this->returnType;
	}
}
