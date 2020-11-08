<?php
namespace NoreSources\SQL\Expression;

use NoreSources\Container;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Expression\Traits\ToggleableTrait;

class SmartCompare implements TokenizableExpressionInterface,
	ToggleableInterface
{

	use ToggleableTrait;

	public static function createWithParameterList(
		TokenizableExpressionInterface $leftOperand, ...$members)
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

		if ($r instanceof Literal)
		{
			if ($r->getDataType() == K::DATATYPE_NULL)
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
		elseif ($r instanceof ExpressionList)
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
