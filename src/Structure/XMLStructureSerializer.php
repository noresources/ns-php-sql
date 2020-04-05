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

use NoreSources\SemanticVersion;
use NoreSources\SQL\Structure\XMLStructureFileConstants as K;

class XMLStructureSerializer extends StructureSerializer
{

	use XMLStructureFileTrait;

	public function __construct(StructureElement $element = null)
	{
		parent::__construct($element);
		$this->schemaVersion = new SemanticVersion('2.0.0');
		$this->schemaNamespaceURI = K::XML_NAMESPACE_BASEURI . '/2.0';
	}

	public function serialize()
	{}

	public function unserializeFromFile($filename)
	{
		$document = new \DOMDocument('1.0', 'utf-8');
		$document->load($filename, LIBXML_XINCLUDE);
		return $this->unserialize($document);
	}

	/**
	 *
	 * @param \DOMDocument|string $serialized
	 *        	Serialized structure
	 */
	public function unserialize($serialized)
	{
		$document = null;

		if ($serialized instanceof \DOMDocument)
			$document = $serialized;
		else
		{
			$document = new \DOMDocument('1.0', 'utf-8');
			$document->loadXML($serialized, LIBXML_XINCLUDE);
		}

		$document->xinclude();

		$importer = new XMLStructureFileImporter();
		$this->structureElement = $importer->importStructureFromDocument($document);
	}
}

