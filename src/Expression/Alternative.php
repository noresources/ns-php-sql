<?php
namespace NoreSources\SQL\Expression;

use NoreSources\Expression as xpr;
use NoreSources\SQL\Statement\BuildContext;

/**
 * CASE expression
 */
class Alternative implements xpr\Expression, Expression, ExpressionReturnType
{

	public function __construct(Expression $when, Expression $then)
	{
		$this->when = $when;
		$this->then = $then;
	}

	public function getExpressionDataType()
	{
		return Helper::getExpressionDataType($this->then);
	}

	public function getCondition()
	{
		return $this->when;
	}

	public function getValue()
	{
		return $this->then;
	}

	public function tokenize(TokenStream &$stream, BuildContext $context)
	{
		return $stream->keyword('when')
			->space()
			->expression($this->when, $context)
			->space()
			->keyword('then')
			->space()
			->expression($this->then, $context);
	}

	/**
	 *
	 * @var Expression
	 */
	private $when;

	/**
	 *
	 * @var Expression
	 */
	private $then;
}

