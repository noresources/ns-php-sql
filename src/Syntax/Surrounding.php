<?php
namespace NoreSources\SQL;

use NoreSources as ns;
use Ferno\Loco as Loco;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\ExpressionEvaluator as X;

/**
 */
class ParenthesisExpression implements Expression
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

	public function tokenize(TokenStream &$stream, StatementContext $context)
	{
		return $stream->text('(')
			->expression($this->expression, $context)
			->text(')');
	}

	public function getExpressionDataType()
	{
		return $this->expression->getExpressionDataType();
	}
}
