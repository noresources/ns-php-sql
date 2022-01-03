<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement\Structure\Traits;

use NoreSources\Container\Container;
use NoreSources\SQL\Structure\Identifier;
use NoreSources\SQL\Structure\StructureElementContainerInterface;
use NoreSources\SQL\Structure\StructureElementInterface;
use NoreSources\SQL\Syntax\TokenStreamContextInterface;
use NoreSources\Type\TypeDescription;

trait IdentifierSelectionTrait
{

	/**
	 * Select the most accurate Identifier from a pre-configured one or from the Token stream
	 * context
	 *
	 * @param Identifier $identifier
	 * @param TokenStreamContextInterface $context
	 * @param string $structureClassname
	 *        	Expected structure element class
	 * @param boolean $preferQualified
	 * @throws \RuntimeException
	 * @return \NoreSources\SQL\Structure\Identifier|unknown
	 */
	protected function selectIdentifier(
		TokenStreamContextInterface $context, $structureClassname,
		Identifier $identifier = null, $preferQualified = false)
	{
		$pivot = $context->getPivot();
		if ($identifier instanceof Identifier)
		{
			if (!$pivot)
				return $identifier;

			if ($preferQualified &&
				Container::count($identifier->getPathParts()) < 2)
			{
				$localName = $identifier->getLocalName();

				if (\is_a($pivot, $structureClassname, true) &&
					$pivot->getName() == $localName)
				{
					return $pivot->getIdentifier();
				}
				elseif ($pivot instanceof StructureElementContainerInterface &&
					$pivot->offsetExists($localName) &&
					($child = $pivot->offsetGet($localName)) &&
					\is_a($child, $structureClassname, true))
				{
					return $child->getIdentifier();
				}
			}

			return $identifier;
		}

		if ($pivot instanceof StructureElementInterface)
		{
			if (\is_a($structureClassname, true))
				return $pivot->getIdentifier();
			if ($pivot instanceof StructureElementContainerInterface &&
				($children = $pivot->getChildElements(
					$structureClassname)) &&
				(Container::count($children) == 1))
			{
				return Container::firstValue($children)->getIdentifier();
			}
		}

		throw new \RuntimeException(
			'Unable to get ' .
			TypeDescription::getLocalName($structureClassname, true) .
			' identifier (Identifier not set, context structure is ' .
			TypeDescription::getLocalName($pivot) . ')');
	}
}
