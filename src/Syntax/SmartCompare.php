<?php
/**
 * Copyright © 2020 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax;

use NoreSources\Container\Container;
use NoreSources\Expression\ExpressionInterface;
use NoreSources\Expression\Set;
use NoreSources\Expression\Value;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Syntax\Traits\ToggleableTrait;

class SmartCompare implements TokenizableExpressionInterface,
	ToggleableInterface
{

	use ToggleableTrait;

	public static function createWithParameterList(
		ExpressionInterface $leftOperand, ...$members)
	{
		if (Container::count($members) > 1)
			return new SmartCompare($leftOperand,
				new ExpressionList($members));
		return new SmartCompare($leftOperand,
			Container::firstValue($members));
	}

	public function __construct(
		TokenizableExpressionInterface $leftOperand,
		TokenizableExpressionInterface $rightOperand)
	{
		$this->leftOperand = $leftOperand;
		$this->rightOperand = $rightOperand;
	}

	public function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$r = $this->rightOperand;

		if ($r instanceof Value)
		{
			$dataType = Evaluator::getDataType($r);
			if ($dataType == K::DATATYPE_NULL)
			{
				$stream->expression($this->leftOperand, $context)
					->space()
					->keyword('is')
					->space();
				if ($this->getToggleState() == false)
					$stream->keyword('not')
						->space()
						->keyword(K::KEYWORD_NULL);
				return $stream;
			}
		}
		elseif ($r instanceof Set)
		{
			$stream->expression($this->leftOperand, $context)->space();
			if ($this->getToggleState() == false)
				$stream->keyword('not')->space();
			$stream->keyword('in')
				->space()
				->text('(')
				->expression($r, $context)
				->text(')');

			return $stream;
		}

		return $stream->expression($this->leftOperand, $context)
			->space()
			->text($this->getToggleState() ? '=' : '<>')
			->space()
			->expression($r, $context);
	}

	/**
	 *
	 * @var TokenizableExpressionInterface
	 */
	private $leftOperand;

	/**
	 *
	 * @var TokenizableExpressionInterface
	 */
	private $rightOperand;
}
