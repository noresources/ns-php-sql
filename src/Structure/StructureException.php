<?php

/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

class StructureException extends \Exception
{

	public function __construct($message, StructureElement $element = null)
	{
		parent::__construct($message);
		$this->structure = $element;
	}

	public function getStructureElement()
	{
		return $this->structure();
	}

	/**
	 *
	 * @var StructureElement
	 */
	private $structure;
}

