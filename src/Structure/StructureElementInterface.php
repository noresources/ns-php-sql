<?php
/**
 * Copyright © 2020 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

use NoreSources\SQL\NameProviderInterface;
use phpDocumentor\Reflection\Element;

interface StructureElementInterface extends NameProviderInterface
{

	/**
	 *
	 * @return string
	 */
	function getName();

	/**
	 *
	 * @return Identifier
	 */
	function getIdentifier();

	/**
	 * Get ancestor
	 *
	 * @param number $depth
	 * @return StructureElementInterface|StructureElementContainerInterface
	 */
	function getParentElement();

	/**
	 * Detach element from its parent
	 */
	function detachElement();

	/**
	 * This method MUST return the same string as getName() when the
	 * element has a name.
	 *
	 * @return Element name if any. Otherwase a unique opaque identifier
	 *
	 */
	function getElementKey();
}