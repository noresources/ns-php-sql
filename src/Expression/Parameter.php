<?php
namespace NoreSources\SQL\Expression;

use NoreSources\SQL as sql;
use NoreSources\Expression as xpr;

class Parameter implements Expression
{
	use xpr\BasicExpressionVisitTrait;

	public $name;

	public function __construct($name)
	{
		$this->name = $name;
	}

	public function tokenize(sql\TokenStream &$stream, sql\BuildContext $context)
	{
		return $stream->parameter($this->name);
	}
}