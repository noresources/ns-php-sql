<?php
namespace NoreSources\SQL\Expression;

use NoreSources\Expression as xpr;

class AlternativeList implements xpr\Expression, TokenizableExpression
{

	public function __construct(TokenizableExpression $subject)
	{
		$this->subject = $subject;
		$this->alternatives = [];
		$this->otherwise = null;
	}

	public function appendAlternative(Alternative $alternative)
	{
		$this->alternatives[] = $alternative;
	}

	public function setOtherwise(TokenizableExpression $else = null)
	{
		$this->otherwise = $else;
	}

	public function tokenize(TokenStream $stream, TokenStreamContext $context)
	{
		$stream->keyword('case')
			->space()
			->expression($this->subject, $context);
		foreach ($this->alternatives as $alternative)
		{
			$stream->space()->expression($alternative, $context);
		}

		if ($this->otherwise instanceof TokenizableExpression)
		{
			$stream->space()
				->keyword('else')
				->space()
				->expression($this->otherwise, $context);
		}

		return $stream;
	}

	/**
	 *
	 * @var Evaluator
	 */
	private $subject;

	/**
	 *
	 * @var array<TokenizableExpression>
	 */
	private $alternatives;

	/**
	 *
	 * @var Evaluator
	 */
	private $otherwise;
}