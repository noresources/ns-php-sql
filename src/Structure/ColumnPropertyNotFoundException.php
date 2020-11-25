<?php
namespace NoreSources\SQL\Structure;

use Psr\Container\NotFoundExceptionInterface;

class ColumnPropertyNotFoundException extends \InvalidArgumentException implements
	NotFoundExceptionInterface
{

	/**
	 *
	 * @param string $property
	 *        	Property ID
	 */
	public function __construct($property)
	{
		parent::__construct($property . ' property not found', 404);
	}
}
