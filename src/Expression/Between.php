<?php
namespace NoreSources\SQL\Expression;

use NoreSources\SQL as sql;
use NoreSources\SQL\Constants as K;
use NoreSources\Expression as xpr;
use NoreSources\SQL\Statement\BuildContext;

class Between implements Expression, ExpressionReturnType
{

	use xpr\BasicExpressionVisitTrait;

	/**
	 * Indicate if the left operand must be inside or ouside the range
	 *
	 * @var boolean
	 */
	public $inside;

	/**
	 *
	 * @param Expression $leftOperand
	 * @param Expression $min
	 * @param Expression $max
	 */
	public function __construct(Expression $leftOperand, Expression $min, Expression $max,
		$inside = true)
	{
		$this->inside = true;
		$this->leftOperand = $leftOperand;
		$this->range = [
			$min,
			$max
		];
	}

	public function getExpressionDataType()
	{
		return K::DATATYPE_BOOLEAN;
	}

	/**
	 *
	 * @return \NoreSources\SQL\Expression\Expression
	 */
	public function getLeftOperand()
	{
		return $this->leftOperand;
	}

	/**
	 *
	 * @return array<integer,Expression>
	 */
	public function getRange()
	{
		return $this->range;
	}

	/**
	 *
	 * @return Expression Range min boundary
	 */
	public function getMin()
	{
		return $this->range[0];
	}

	/**
	 *
	 * @return Expression Range max boundary
	 */
	public function getMax()
	{
		return $this->range[1];
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\Tokenizable::tokenize()
	 */
	public function tokenize(TokenStream &$stream, BuildContext $context)
	{
		$stream->expression($this->leftOperand, $context);
		if (!$this->inside)
			$stream->space()->text('NOT');

		return $stream->space()
			->text('BETWEEN')
			->space()
			->expression($this->range[0], $context)
			->space()
			->text('AND')
			->space()
			->expression($this->range[1], $context);
	}

	/**
	 *
	 * @var Expression
	 */
	private $leftOperand;

	/**
	 *
	 * @var \array<integer,Expression>
	 */
	private $range;
}