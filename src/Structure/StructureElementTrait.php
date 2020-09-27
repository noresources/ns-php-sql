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

use NoreSources\TypeDescription;
use NoreSources\SQL\DBMS\PlatformInterface;

trait StructureElementTrait
{

	public function getName()
	{
		return $this->elementName;
	}

	public function getPath(PlatformInterface $platform = null)
	{
		$s = ($platform instanceof PlatformInterface) ? $platform->quoteIdentifier(
			$this->getName()) : $this->getName();
		$p = $this->getParentElement();
		while ($p && !($p instanceof DatasourceStructure))
		{
			$s = (($platform instanceof PlatformInterface) ? $platform->quoteIdentifier(
				$p->getName()) : $p->getName()) . '.' . $s;
			$p = $p->getParentElement();
		}

		return $s;
	}

	public function getParentElement($depth = 1)
	{
		$p = $this->parentElement;
		while ($p && ($depth > 1))
		{
			$p = $p->parentElement;
			$depth--;
		}

		return $p;
	}

	public function getRootElement()
	{
		$res = $this;
		while ($res->getParentElement())
		{
			$res = $res->getParentElement();
		}

		return $res;
	}

	public function detachElement()
	{
		$p = $this->getParentElement();
		$this->parentElement = null;
		if ($p instanceof StructureElementContainerInterface)
			$p->offsetUnset($this->getName());
	}

	public function setParentElement(
		StructureElementContainerInterface $parent = null)
	{
		$this->parentElement = $parent;
	}

	protected function initializeStructureElement($name,
		StructureElementContainerInterface $parent = null)
	{
		if (!(is_string($name) && strlen($name)))
			throw new StructureException(
				'Invalid element name (' .
				TypeDescription::getName($name) . ')');
		$this->elementName = $name;
		$this->parentElement = $parent;
	}

	protected function cloneStructureElement()
	{}

	/**
	 *
	 * @var string
	 */
	protected $elementName;

	/**
	 *
	 * @var StructureElement
	 */
	protected $parentElement;
}