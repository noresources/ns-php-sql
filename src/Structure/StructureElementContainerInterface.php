<?php
/**
 * Copyright © 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

use NoreSources\SQL\AssetMapInterface;

interface StructureElementContainerInterface extends AssetMapInterface,
	\ArrayAccess
{

	/**
	 *
	 * @return array
	 */
	function getChildElements();

	/**
	 *
	 * @param StructureElementInterface $child
	 * @return StructureElement
	 */
	function appendElement(StructureElementInterface $element);

	/**
	 *
	 * @param StructureElementInterface $tree
	 * @return StructureElementInterface
	 */
	function findDescendant($tree);
}