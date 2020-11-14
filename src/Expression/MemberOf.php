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

use NoreSources\Expression\ExpressionInterface;
use NoreSources\Expression\Set;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DataTypeProviderInterface;
use NoreSources\SQL\Expression\Traits\ToggleableTrait;

/**
 * IN(), NOT IN() SQL operator
 */
class MemberOf extends Set implements TokenizableExpressionInterface,
	DataTypeProviderInterface, ToggleableInterface
{
	use ToggleableTrait;

	/**
	 *
	 * @param ExpressionInterface $leftOperand
	 * @param Evaluable ...$members
	 * @return \MemberOf
	 */
	public static function createWithParameterList(
		ExpressionInterface $leftOperand, ...$members)
	{
		return new MemberOf($leftOperand,
			ExpressionList::flatten(...$members));
	}

	/**
	 *
	 * @param ExpressionInterface $leftOperand
	 * @param Evaluable[] $members
	 */
	public function __construct(ExpressionInterface $leftOperand,
		$members)
	{
		parent::__construct();
		$this->leftOperand = $leftOperand;
		foreach ($members as $x)
			$this->append(Evaluator::evaluate($x));
	}

	/**
	 *
	 * @param Evaluable[] $members
	 * @return \NoreSources\SQL\Expression\MemberOf
	 */
	public function members($members)
	{
		foreach ($members as $x)
			$this->append(Evaluator::evaluate($x));
		return $this;
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see ExpressionInterface::tokenize()
	 */
	public function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$stream->expression($this->leftOperand, $context);
		if (!$this->getToggleState())
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

	public function getDataType()
	{
		return K::DATATYPE_BOOLEAN;
	}

	protected function isValidElement($element)
	{
		return ($element instanceof ExpressionInterface);
	}

	/**
	 *
	 * @var ExpressionInterface
	 */
	private $leftOperand;
}