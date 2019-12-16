<?php
namespace NoreSources\SQL\Expression;

use NoreSources\Expression as xpr;
use NoreSources\SQL\Statement\BuildContext;

/**
 * CASE expression
 */
class Alternative implements xpr\Expression, Expression, ExpressionReturnType
{

	public function __construct(Expression $when, Expression $then)
	{
		$this->when = $when;
		$this->then = $then;
	}

	public function getExpressionDataType()
	{
		return Helper::getExpressionDataType($this->then);
	}

	public function getCondition()
	{
		return $this->when;
	}

	public function getValue()
	{
		return $this->then;
	}

	public function tokenize(TokenStream &$stream, BuildContext $context)
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
	 * @var Expression
	 */
	private $when;

	/**
	 *
	 * @var Expression
	 */
	private $then;
}

class AlternativeList implements xpr\Expression, Expression
{
	use xpr\BasicExpressionVisitTrait;

	public function __construct(Expression $subject)
	{
		$this->subject = $subject;
		$this->alternatives = [];
		$this->otherwise = null;
	}

	public function appendAlternative(Alternative $alternative)
	{
		$this->alternatives[] = $alternative;
	}

	public function setOtherwise(Expression $else = null)
	{
		$this->otherwise = $else;
	}

	public function tokenize(TokenStream &$stream, BuildContext $context)
	{
		$stream->keyword('case')
			->space()
			->expression($this->subject, $context);
		foreach ($this->alternatives as $alternative)
		{
			$stream->space()->expression($alternative, $context);
		}

		if ($this->otherwise instanceof Expression)
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
	 * @var array<Expression>
	 */
	private $alternatives;

	/**
	 *
	 * @var Evaluator
	 */
	private $otherwise;
}