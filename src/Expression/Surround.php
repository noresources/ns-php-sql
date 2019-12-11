<?php
namespace NoreSources\SQL\Expression;

use NoreSources\SQL as sql;
use NoreSources\Expression as xpr;

class Surround implements Expression, ExpressionReturnType
{
	use xpr\BasicExpressionVisitTrait;

	/**
	 *
	 * @param Expression $expression
	 * @param string $open
	 * @param string $close
	 */
	public function __construct(Expression $expression, $open = '(', $close = ')')
	{
		$this->expression = $expression;
		$this->openingText = $open;
		$this->closingText = $close;
	}

	/**
	 *
	 * @return \NoreSources\Expression\Expression
	 */
	public function getExpression()
	{
		return $this->expression;
	}

	/**
	 *
	 * @return string
	 */
	public function getOpeningText()
	{
		return $this->openingText;
	}

	/**
	 *
	 * @return unknown
	 */
	public function getClosingText()
	{
		return $this->getClosingText();
	}

	public function tokenize(sql\TokenStream &$stream, sql\BuildContext $context)
	{
		return $stream->text($this->openingText)
			->expression($this->expression, $context)
			->text($this->closingText);
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\Expression\ExpressionReturnType::getExpressionDataType()
	 */
	public function getExpressionDataType()
	{
		return Helper::getExpressionDataType($this->expression);
	}

	/**
	 *
	 * @var Expression
	 */
	private $expression;

	/**
	 *
	 * @var string
	 */
	private $openingText;

	/**
	 *
	 * @var string
	 */
	private $closingText;
}