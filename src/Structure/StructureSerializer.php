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

use NoreSources\SQL\Constants as K;

/**
 */
abstract class StructureSerializer implements \Serializable
{

	/**
	 * Unserialize from file
	 *
	 * @param string $filename
	 */
	public function userializeFromFile($filename)
	{
		return $this->unserialize(file_get_contents($filename));
	}

	/**
	 * Serialize to file
	 *
	 * @param string $filename
	 */
	public function serializeToFile($filename)
	{
		return file_put_contents($filename, $this->serialize());
	}

	public function __construct(StructureElement $element = null)
	{
		$this->structureElement = $element;
	}

	/**
	 *
	 * @property-read StructureElement $structureElement
	 * @param string $member
	 * @throws \InvalidArgumentException
	 * @return \NoreSources\SQL\StructureElement
	 */
	public function __get($member)
	{
		if ($member == 'structureElement')
		{
			return $this->structureElement;
		}

		throw new \InvalidArgumentException($member);
	}

	/**
	 *
	 * @var StructureElement
	 */
	protected $structureElement;
}
