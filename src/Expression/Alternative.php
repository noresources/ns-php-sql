<?php
namespace NoreSources\SQL\Expression;

use NoreSources\Expression as xpr;

/**
 * CASE expression
 */
class Alternative implements xpr\Expression, TokenizableExpression, ExpressionReturnType
{

	public function __construct(TokenizableExpression $when, TokenizableExpression $then)
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

	public function tokenize(TokenStream $stream, TokenStreamContext $context)
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
	 * @var TokenizableExpression
	 */
	private $when;

	/**
	 *
	 * @var TokenizableExpression
	 */
	private $then;
}

