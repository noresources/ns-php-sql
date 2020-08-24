<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\Expression;

use NoreSources\Expression\Set;
use NoreSources\SQL\Constants as K;

/**
 * IN(), NOT IN() SQL operator
 */
class MemberOf extends Set implements TokenizableExpressionInterface,
	ExpressionReturnTypeInterface
{

	/**
	 * Indicate if the left operand should be a member of the set or not
	 *
	 * @var boolean
	 */
	public $memberOf;

	/**
	 *
	 * @param TokenizableExpressionInterface $leftOperand
	 * @param array $expressionList
	 *        	List of expression
	 * @param boolean $memberOf
	 *        	Indicate if @c $leftOperand should be a momber of the @c $expressionList or not
	 */
	public function __construct(
		TokenizableExpressionInterface $leftOperand,
		$expressionList = array(), $memberOf = true)
	{
		parent::__construct();
		$this->leftOperand = $leftOperand;
		$this->memberOf = $memberOf;
		foreach ($expressionList as $x)
			$this->append(Evaluator::evaluate($x));
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\TokenizableExpressionInterface::tokenize()
	 */
	public function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
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
		return ($element instanceof TokenizableExpressionInterface);
	}

	/**
	 *
	 * @var TokenizableExpressionInterface
	 */
	private $leftOperand;
}