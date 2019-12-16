<?php
namespace NoreSources\SQL\Expression;

/**
 * Structure element path or alias
 */
abstract class StructureElementIdentifier implements Expression
{

	public $path;

	public function __construct($path)
	{
		$this->path = $path;
	}
}