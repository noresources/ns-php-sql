<?php
/**
 * Copyright © 2020 - 2022 by Renaud Guillard (dev@nore.fr)
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
	 * @param string $typeFilter
	 *        	Return only child elements of the given class typename
	 * @return array<StructureElementInterface>
	 */
	function getChildElements($typeFilter = null);

	/**
	 *
	 * @param StructureElementInterface $child
	 * @return StructureElement
	 */
	function appendElement(StructureElementInterface $element);

	/**
	 *
	 * @param Identifier $identifier
	 * @return StructureElementInterface
	 */
	function findDescendant($identifier);
}