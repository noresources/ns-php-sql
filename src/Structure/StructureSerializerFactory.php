<?php
/**
 * Copyright © 2020 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure;

use NoreSources\SingletonTrait;
use NoreSources\Container\Container;
use NoreSources\MediaType\MediaType;
use NoreSources\MediaType\MediaTypeFactory;
use NoreSources\MediaType\MediaTypeInterface;
use NoreSources\SQL\Structure\Exporter\StructureFileExporterInterface;
use NoreSources\SQL\Structure\Exporter\XMLStructureFileExporter;
use NoreSources\SQL\Structure\Importer\StructureFileImporterInterface;
use NoreSources\SQL\Structure\Importer\XMLStructureFileImporter;
use NoreSources\Type\TypeDescription;

/**
 * Provide serialization and deserialization of StructureElement
 */
class StructureSerializerFactory
{

	use SingletonTrait;

	/**
	 *
	 * @param string $filename
	 *        	Structure description file path
	 */
	/**
	 *
	 * @param string $filename
	 * @throws StructureSerializationException
	 * @return StructureElement
	 */
	public function structureFromFile($filename)
	{
		if (!isset($this))
			return self::getInstance()->structureFromFile($filename);

		$mediaType = MediaTypeFactory::createFromMedia($filename);
		$importer = Container::keyValue($this->fileImporters,
			\strval($mediaType));
		if (!\is_subclass_of($importer,
			StructureFileImporterInterface::class, true))
			$importer = Container::keyValue($this->fileImporters,
				$mediaType->getStructuredSyntax());

		if (!\is_subclass_of($importer,
			StructureFileImporterInterface::class, true))
			throw new StructureSerializationException(
				'No ' .
				TypeDescription::getLocalName(
					StructureFileImporterInterface::class) .
				' found for file "' . $filename . '" (' .
				\strval($mediaType) . ')');

		if (!($importer instanceof StructureFileImporterInterface))
		{
			$cls = new \ReflectionClass($importer);
			$importer = $cls->newInstance();
		}

		return $importer->importStructureFromFile($filename);
	}

	/**
	 *
	 * @param StructureElementInterface $structure
	 * @param string $filename
	 */
	public function structureToFile(
		StructureElementInterface $structure, $filename,
		$mediaType = null)
	{
		if (\is_string($mediaType))
			$mediaType = MediaTypeFactory::createFromString($mediaType);

		if (!($mediaType instanceof MediaType))
			$mediaType = MediaTypeFactory::createFromMedia($filename);

		$exporter = Container::keyValue($this->fileExporters,
			\strval($mediaType));
		if (!\is_subclass_of($exporter,
			StructureFileExporterInterface::class, true))
			$exporter = Container::keyValue($this->fileExporters,
				$mediaType->getStructuredSyntax());

		if (!\is_subclass_of($exporter,
			StructureFileExporterInterface::class, true))
			throw new StructureSerializationException(
				'No ' .
				TypeDescription::getLocalName(
					StructureFileExporterInterface::class) .
				' found for file ' . $filename . '(' .
				\strval($mediaType) . ')');

		if (!($exporter instanceof StructureFileExporterInterface))
		{
			$cls = new \ReflectionClass($exporter);
			$exporter = $cls->newInstance();
		}

		return $exporter->exportStructureToFile($structure, $filename);
	}

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

		$this->registerFileExporter('text/xml',
			XMLStructureFileExporter::class);
		$this->registerFileExporter('application/xml',
			XMLStructureFileExporter::class);
		$this->registerFileImporter('text/xml',
			XMLStructureFileImporter::class);
		$this->registerFileImporter('application/xml',
			XMLStructureFileImporter::class);
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

