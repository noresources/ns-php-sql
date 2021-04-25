<?php

/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure\Traits;

use NoreSources\TypeDescription;
use NoreSources\SQL\DBMS\PlatformInterface;
use NoreSources\SQL\Structure\DatasourceStructure;
use NoreSources\SQL\Structure\Identifier;
use NoreSources\SQL\Structure\StructureElementContainerInterface;
use NoreSources\SQL\Structure\StructureElementInterface;
use NoreSources\SQL\Structure\StructureException;
use ArrayAccess;

trait StructureElementTrait
{

	public function getName()
	{
		return $this->elementName;
	}

	public function getIdentifier()
	{
		$a = [
			$this->getName()
		];
		while (($p = $this->getParentElement()) &&
			!($p instanceof DatasourceStructure))
		{
			array_unshift($a, $p->getName());
		}

		return Identifier::make($a);
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
		if ($p instanceof ArrayAccess)
			$p->offsetUnset($this->getName());
	}

	public function setParentElement(
		StructureElementInterface $parent = null)
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