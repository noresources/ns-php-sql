<?php
/**
 * Copyright © 2020 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

use NoreSources\SQL\Structure\Traits\StructureElementContainerTrait;
use NoreSources\SQL\Structure\Traits\StructureElementTrait;
use NoreSources\Type\TypeDescription;

/**
 * Top-level structure container definition
 */
class DatasourceStructure implements StructureElementInterface,
	StructureElementContainerInterface
{

	use StructureElementContainerTrait;
	use StructureElementTrait;

	/**
	 *
	 * @param string $name
	 *        	Datasource class name
	 */
	public function __construct($name = 'datasource')
	{
		$name = (\is_string($name) && \strlen($name)) ? $name : TypeDescription::getLocalName(
			static::class);
		$this->initializeStructureElementContainer();
	}

	public function __clone()
	{
		$this->cloneStructureElement();
		$this->cloneStructureElementContainer();
		if (isset($this->metadata))
			$this->metadata = clone $this->metadata;
	}

	/**
	 *
	 * @return StructureMetadata
	 */
	public function getMetadata()
	{
		if (!isset($this->metadata))
			$this->metadata = new StructureMetadata();
		return $this->metadata;
	}

	/**
	 *
	 * @var StructureMetadata
	 */
	private $metadata;
}

