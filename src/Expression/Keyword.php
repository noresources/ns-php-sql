<?php
namespace NoreSources\SQL\Expression;

use NoreSources\SQL as sql;
use NoreSources\Expression as xpr;

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

	public function tokenize(sql\TokenStream &$stream, sql\StatementContext $context)
	{
		return $stream->keyword($context->getKeyword($this->keyword));
	}
}