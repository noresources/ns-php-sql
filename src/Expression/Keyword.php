<?php
namespace NoreSources\SQL\Expression;

use NoreSources\SQL as sql;
use NoreSources\Expression as xpr;
use NoreSources\SQL\Statement\BuildContext;

class Keyword implements Expression
{
	use xpr\BasicExpressionVisitTrait;

	/**
	 * Keyword constant.
	 * One of Constants\KEYWORD_*.
	 *
	 * @var integer
	 */
	public $keyword;

	/**
	 *
	 * @param integer $keyword
	 */
	public function __construct($keyword)
	{
		$this->keyword = $keyword;
	}

	public function tokenize(TokenStream &$stream, BuildContext $context)
	{
		return $stream->keyword($context->getKeyword($this->keyword));
	}
}