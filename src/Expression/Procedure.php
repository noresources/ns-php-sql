<?php
namespace NoreSources\SQL\Expression;

use NoreSources\SQL as sql;
use NoreSources\Expression as xpr;

class Procedure extends xpr\Procedure implements Expression
{

	public function __construct($name, $arguments = array())
	{
		parent::__construct($name, $arguments);
	}

	public function tokenize(sql\TokenStream &$stream, sql\StatementContext $context)
	{
		return $stream->keyword($this->getFunctionName())
			->text('(');
		$index = 0;
		foreach ($this->getArguments() as $a)
		{
			if ($index++ > 0)
				$stream->text(', ');

			$stream->expression($a, $context);
		}
		return $stream->text(')');
	}
}