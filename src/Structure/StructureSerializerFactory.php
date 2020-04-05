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

use NoreSources\Container;
use NoreSources\StaticallyCallableSingletonTrait;
use NoreSources\MediaType\MediaTypeFactory;
use NoreSources\MediaType\MediaTypeInterface;

class StructureSerializerFactory
{

	use StaticallyCallableSingletonTrait;

	/**
	 *
	 * @param string $filename
	 *        	Structure description file path
	 */
	/**
	 *
	 * @param string $filename
	 * @throws StructureException
	 * @return StructureElement
	 */
	public function structureFromFile($filename)
	{
		if (!isset($this))
			return self::getInstance()->structureFromFile($filename);

		$mediaType = MediaTypeFactory::fromMedia($filename);
		$importer = Container::keyValue($this->fileImporters, \strval($mediaType));
		if (!\is_subclass_of($importer, StructureFileImporterInterface::class, true))
			$importer = Container::keyValue($this->fileImporters, $mediaType->getStructuredSyntax());

		if (!\is_subclass_of($importer, StructureFileImporterInterface::class, true))
			throw new StructureException(
				'No ' . StructureFileImporterInterface::class . ' found for file "' . $filename .
				'" (' . \strval($mediaType) . '"');

		if (!($importer instanceof StructureFileImporterInterface))
		{
			$cls = new \ReflectionClass($importer);
			$importer = $cls->newInstance();
		}

		return $importer->importStructureFromFile($filename);
	}

	/**
	 *
	 * @param StructureElement $structure
	 * @param string $filename
	 */
	public function structureToFile(StructureElement $structure, $filename)
	{}

	/**
	 *
	 * @param MediaTypeInterface|string $mediaType
	 *        	Media type
	 * @param StructureFileImporterInterface|string $fileImporter
	 *        	Importer class or class name
	 */
	public function registerFileImporter($mediaType, $fileImporter)
	{
		$this->fileImporters[\strval($mediaType)] = $fileImporter;
		if ($mediaType instanceof MediaTypeInterface)
		{
			$syntax = $mediaType->getStructuredSyntax();
			$this->fileImporters[$syntax] = $fileImporter;
		}
	}

	/**
	 *
	 * @param MediaTypeInterface|string $mediaType
	 *        	Media type
	 * @param StructureFileExporterInterface|string $fileExporter
	 *        	Exporter class or class name
	 */
	public function registerFileExporter($mediaType, $fileExporter)
	{
		$this->fileExporters[\strval($mediaType)] = $fileExporter;
		if ($mediaType instanceof MediaTypeInterface)
		{
			$syntax = $mediaType->getStructuredSyntax();
			$this->fileExporters[$syntax] = $fileExporter;
		}
	}

	public function __construct()
	{
		$this->fileImporters = [];
		$this->fileExporters = [];

		$this->registerFileExporter('text/xml', XMLStructureFileExporter::class);
		$this->registerFileImporter('text/xml', XMLStructureFileImporter::class);
	}

	/**
	 *
	 * @var StructureFileExporterInterface[]
	 */
	private $fileExporters;

	/**
	 *
	 * @var StructureFileImporterInterface
	 */
	private $fileImporters;
}

