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

use NoreSources\SQL\Statement\StatementBuilderInterface;

interface StructureElementInterface
{

	/**
	 *
	 * @return string
	 */
	function getName();

	/**
	 *
	 * @param \NoreSources\SQL\Statement\StatementTokenStreamContext $builder
	 * @return string
	 */
	function getPath(StatementBuilderInterface $builder = null);

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