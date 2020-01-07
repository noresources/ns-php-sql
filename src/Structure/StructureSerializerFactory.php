<?php
namespace NoreSources\SQL;

use NoreSources as ns;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Expression\Expression;
use NoreSources\SQL\Expression\Evaluator as X;
use NoreSources\SQL\Expression\Keyword;

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
			if (ns\Container::keyExists(self::$mimeTypeClassMap, $type))
				return new \ReflectionClass(self::$mimeTypeClassMap[$type]);
		}

		$x = pathinfo($filename, \PATHINFO_EXTENSION);
		if (ns\Container::keyExists(self::$fileExtensionClassMap, $x))
			return new \ReflectionClass(self::$fileExtensionClassMap[$x]);

		throw new StructureException(
			'Unable to find a StructureSerializer for file ' . basename($filename));
	}

	public static function initialize()
	{
		if (!\is_array(self::$mimeTypeClassMap))
		{
			self::$mimeTypeClassMap = [
				'text/xml' => XMLStructureSerializer::class,
				'application/json' => JSONStructureSerializer::class
			];
		}

		if (!\is_array(self::$fileExtensionClassMap))
		{
			self::$fileExtensionClassMap = [
				'xml' => XMLStructureSerializer::class,
				'json' => JSONStructureSerializer::class
			];
		}
	}

	private static $mimeTypeClassMap;

	private static $fileExtensionClassMap;
}

StructureSerializerFactory::initialize();