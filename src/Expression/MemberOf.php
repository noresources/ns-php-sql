<?php
namespace NoreSources\SQL\Expression;

use NoreSources\SQL as sql;
use NoreSources\SQL\Constants as K;
use NoreSources\Expression as xpr;

/**
 * In operator
 */
class MemberOf extends xpr\Set implements Expression, ExpressionReturnType
{

	/**
	 * Indicate if the left operand should be a member of the set or not
	 *
	 * @var unknown
	 */
	public $memberOf;

	/**
	 *
	 * @param Expression $leftOperand
	 * @param array $expressionList
	 *        	List of expression
	 * @param boolean $memberOf
	 *        	Indicate if @c $leftOperand should be a momber of the @c $expressionList or not
	 */
	public function __construct(Expression $leftOperand, $expressionList = array(), $memberOf = true)
	{
		parent::__construct($expressionList);
		$this->leftOperand = $leftOperand;
		$this->memberOf = $memberOf;
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\Tokenizable::tokenize()
	 */
	public function tokenize(sql\TokenStream &$stream, sql\StatementContext $context)
	{
		$stream->expression($this->leftOperand, $context);
		if (!$this->memberOf)
			$stream->space()->keyword('NOT');
		$stream->space()
			->keyword('IN')
			->space()
			->text('(');

		$index = 0;
		foreach ($this as $x)
		{
			if ($index++ > 0)
				$stream->text(', ');

			$stream->expression($x, $context);
		}

		return $stream->text(')');
	}

	public function getExpressionDataType()
	{
		return K::DATATYPE_BOOLEAN;
	}

	protected function isValidElement($element)
	{
		return ($element instanceof Expression);
	}

	/**
	 *
	 * @var Expression
	 */
	private $leftOperand;
}