<?php
namespace NoreSources\SQL\Structure;

interface StructureResolverAwareInterface
{

	/**
	 *
	 * @param StructureResolverInterface $resolver
	 */
	function setStructureResolver(StructureResolverInterface $resolver);
}
