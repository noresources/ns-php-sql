<?php
/**
 * Copyright © 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

use NoreSources\SQL\NameProviderInterface;
use NoreSources\SQL\DBMS\PlatformInterface;

interface StructureElementInterface extends NameProviderInterface
{

	/**
	 *
	 * @return string
	 */
	function getName();

	/**
	 *
	 * @param PlatformInterface $platform
	 * @return string
	 */
	function getPath(PlatformInterface $platform = null);

	/**
	 *
	 * @return Identifier
	 */
	function getIdentifier();

	/**
	 * Get ancestor
	 *
	 * @param number $depth
	 * @return StructureElementContainerInterface
	 */
	function getParentElement();

	/**
	 *
	 * @return StructureElement
	 */
	function getRootElement();

	/**
	 * Detach element from its parent
	 */
	function detachElement();
}