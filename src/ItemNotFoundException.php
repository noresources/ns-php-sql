<?php
/**
 * Copyright © 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL;

use Psr\Container\NotFoundExceptionInterface;

/**
 * Base exception of ContainerInterface types
 */
class ItemNotFoundException extends \Exception implements
	NotFoundExceptionInterface
{

	/**
	 *
	 * @param string $kind
	 *        	Item type name
	 * @param mixed $id
	 *        	Missing item identifier
	 */
	public function __construct($kind, $id)
	{
		$id = (\is_numeric($id) ? $id : '"' . $id . '"');
		parent::__construct($kind . ' ' . $id . ' not found');
	}
}
