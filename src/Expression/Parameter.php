<?php
namespace NoreSources\SQL\Expression;

use NoreSources\SQL\Statement\BuildContext;

class Parameter implements Expression
{

	public $name;

	public function __construct($name)
	{
		$this->name = $name;
	}

	public function tokenize(TokenStream &$stream, BuildContext $context)
	{
		return $stream->parameter($this->name);
	}
}