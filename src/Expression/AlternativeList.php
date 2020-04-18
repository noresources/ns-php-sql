<?php
namespace NoreSources\SQL\Expression;

/**
 * List of CASE + ELSE expression
 *
 * @see https://www.sqlite.org/lang_expr.html
 */
class AlternativeList implements TokenizableExpressionInterface
{

	public function __construct(TokenizableExpressionInterface $subject)
	{
		$this->subject = $subject;
		$this->alternatives = [];
		$this->otherwise = null;
	}

	public function appendAlternative(Alternative $alternative)
	{
		$this->alternatives[] = $alternative;
	}

	public function setOtherwise(TokenizableExpressionInterface $else = null)
	{
		$this->otherwise = $else;
	}

	public function tokenize(TokenStream $stream, TokenStreamContextInterface $context)
	{
		$stream->keyword('case')
			->space()
			->expression($this->subject, $context);
		foreach ($this->alternatives as $alternative)
		{
			$stream->space()->expression($alternative, $context);
		}

		if ($this->otherwise instanceof TokenizableExpressionInterface)
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
	 * @var array<TokenizableExpressionInterface>
	 */
	private $alternatives;

	/**
	 *
	 * @var Evaluator
	 */
	private $otherwise;
}