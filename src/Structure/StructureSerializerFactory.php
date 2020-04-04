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

class StructureSerializerFactory
{

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
	public static function structureFromFile($filename)
	{
		if (!file_exists($filename))
			throw new StructureException('Structure definition file not found');

		$type = mime_content_type($filename);

		$serializerClass = self::getSerializeClassForFile($filename);
		$serializer = $serializerClass->newInstance();
		$serializer->unserializeFromFile($filename);
		return $serializer->structureElement;
	}

	/**
	 *
	 * @param StructureElement $structure
	 * @param string $filename
	 */
	public static function structureToFile(StructureElement $structure, $filename)
	{
		$serializerClass = self::getSerializeClassForFile($filename);
		$serializer = $serializerClass->newInstance($structure);
		$serializer->serializeToFile($filename);
	}

	/**
	 *
	 * @param string $filename
	 * @throws StructureException
	 * @return \ReflectionClass
	 */
	public static function getSerializeClassForFile($filename)
	{
		if (\is_file($filename))
		{
			$type = mime_content_type($filename);
			if (Container::keyExists(self::$mimeTypeClassMap, $type))
				return new \ReflectionClass(self::$mimeTypeClassMap[$type]);
		}

		$x = pathinfo($filename, \PATHINFO_EXTENSION);
		if (Container::keyExists(self::$fileExtensionClassMap, $x))
			return new \ReflectionClass(self::$fileExtensionClassMap[$x]);

		throw new StructureException(
			'Unable to find a StructureSerializer for file ' . basename($filename));
	}

	public static function initialize()
	{
		if (!\is_array(self::$mimeTypeClassMap))
		{
			self::$mimeTypeClassMap = [
				'text/xml' => XMLStructureSerializer::class
			];
		}

		if (!\is_array(self::$fileExtensionClassMap))
		{
			self::$fileExtensionClassMap = [
				'xml' => XMLStructureSerializer::class
			];
		}
	}

	private static $mimeTypeClassMap;

	private static $fileExtensionClassMap;
}

StructureSerializerFactory::initialize();