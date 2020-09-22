<?php
namespace NoreSources\SQL\DBMS;

use NoreSources\Http\CaseInsensitiveKeyMapTrait;
use Psr\Container\ContainerInterface;

class TypeRegistry implements \ArrayAccess, \Countable,
	ContainerInterface, \IteratorAggregate
{

	use CaseInsensitiveKeyMapTrait;

	/**
	 *
	 * @param TypeInterface[] $array
	 *        	Type map
	 */
	public function __construct($array = array())
	{
		$this->initializeCaseInsensitiveKeyMapTrait($array);
	}
}
