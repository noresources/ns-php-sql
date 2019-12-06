<?php
namespace NoreSources\SQL\Expression;

use NoreSources\SQL as sql;
use NoreSources\Expression as xpr;

/**
 * Structure element path or alias
 */
abstract class StructureElementIdentifier implements Expression
{
	use xpr\BasicExpressionVisitTrait;

	public $path;

	public function __construct($path)
	{
		$this->path = $path;
	}
}