<?php
namespace NoreSources\SQL\Expression;

use NoreSources\SQL as sql;
use NoreSources\Expression as xpr;
use NoreSources\SQL\Statement\BuildContext;

class Parameter implements Expression
{
	use xpr\BasicExpressionVisitTrait;

	public $name;

	public function __construct($name)
	{
		$this->name = $name;
	}

	public function tokenize(sql\TokenStream &$stream, BuildContext $context)
	{
		return $stream->parameter($this->name);
	}
}