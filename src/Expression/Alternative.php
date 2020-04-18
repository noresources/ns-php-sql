<?php
namespace NoreSources\SQL\Expression;

/**
 * CASE expression
 */
class Alternative implements TokenizableExpressionInterface, ExpressionReturnTypeInterface
{

	public function __construct(TokenizableExpressionInterface $when, TokenizableExpressionInterface $then)
	{
		$this->when = $when;
		$this->then = $then;
	}

	public function getExpressionDataType()
	{
		return ExpressionHelper::getExpressionDataType($this->then);
	}

	public function getCondition()
	{
		return $this->when;
	}

	public function getValue()
	{
		return $this->then;
	}

	public function tokenize(TokenStream $stream, TokenStreamContextInterface $context)
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
	 * @var TokenizableExpressionInterface
	 */
	private $when;

	/**
	 *
	 * @var TokenizableExpressionInterface
	 */
	private $then;
}

