<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure\Traits;

use NoreSources\Container;
use NoreSources\SemanticVersion;
use NoreSources\TypeDescription;
use NoreSources\SQL\Structure\ColumnStructure;
use NoreSources\SQL\Structure\DatasourceStructure;
use NoreSources\SQL\Structure\NamespaceStructure;
use NoreSources\SQL\Structure\StructureElementInterface;
use NoreSources\SQL\Structure\StructureSerializationException;
use NoreSources\SQL\Structure\TableStructure;
use NoreSources\SQL\Structure\XMLStructureFileConstants as K;

/**
 * Common code to XML structure file importer and exporter
 */
trait XMLStructureFileTrait
{

	/**
	 *
	 * @param SemanticVersion $schemaVersion
	 *        	XML schema version
	 *
	 * @return string XML namespace URI for the given schema version
	 */
	public static function getXmlNamespaceURI(
		SemanticVersion $schemaVersion)
	{
		$s = K::XML_NAMESPACE_BASEURI;
		if ($schemaVersion->getIntegerValue() > 10099)
			$s .= '/' .
				$schemaVersion->slice(SemanticVersion::MAJOR,
					SemanticVersion::MINOR);
		return $s;
	}

	/**
	 * Get XML node name for the given StructureElement
	 *
	 * @param StructureElementInterface $element
	 * @param SemanticVersion $schemaVersion
	 *        	XML schema version
	 * @throws \InvalidArgumentException
	 * @return string Node name
	 */
	public static function getXmlNodeName($element,
		SemanticVersion $schemaVersion)
	{
		if ($element instanceof StructureElementInterface)
			$element = get_class($element);

		if ($element == DatasourceStructure::class)
			return 'datasource';
		elseif ($element == NamespaceStructure::class)
		{
			if ($schemaVersion->getIntegerValue() < 20000)
				return 'database';
			return 'namespace';
		}
		elseif ($element == TableStructure::class)
			return 'table';
		elseif ($element == ColumnStructure::class)
			return 'column';

		throw new \InvalidArgumentException(
			TypeDescription::getName($element) . ' is not a ' .
			StructureElementInterface::class);
	}

	public static function getDataTypeFromNodeName($nodeName,
		SemanticVersion $schemaVersion)
	{
		if (!Container::isArray(self::$dataTypeNodeNames))
			self::initializeDataTypeNodeNames();

		foreach (self::$dataTypeNodeNames as $dataType => $name)
		{
			if ($name == $nodeName)
				return $dataType;
		}

		return K::DATATYPE_UNDEFINED;
	}

	public static function getDataTypeNode($dataType,
		SemanticVersion $schemaVersion)
	{
		if (!Container::isArray(self::$dataTypeNodeNames))
			self::initializeDataTypeNodeNames();

		return Container::keyValue(self::$dataTypeNodeNames, $dataType,
			'string');
	}

	/**
	 *
	 * @param integer $columntDataType
	 * @param integer $defaultValueType
	 * @param SemanticVersion $schemaVersion
	 * @return string Node name for the default value sub node.
	 */
	public static function getDefaultNodeValueNodeName($columntDataType,
		$defaultValueType, SemanticVersion $schemaVersion)
	{
		if ($defaultValueType == K::DATATYPE_UNDEFINED)
			$defaultValueType = $columntDataType;

		switch ($defaultValueType)
		{
			case K::DATATYPE_NULL:
				return 'null';
			case K::DATATYPE_BOOLEAN:
				return 'boolean';
			case K::DATATYPE_DATE:
			case K::DATATYPE_DATETIME:
			case K::DATATYPE_TIME:
			case K::DATATYPE_TIMESTAMP:
				if ($schemaVersion->getIntegerValue() >= 20000)
					return 'timestamp';
				return 'datetime';
			case K::DATATYPE_FLOAT:
			case K::DATATYPE_INTEGER:
			case K::DATATYPE_NUMBER:
				return 'number';
			case K::DATATYPE_NULL:
				return 'null';
		}

		switch ($columntDataType)
		{
			case K::DATATYPE_BINARY:
				return 'base64Binary';
		}

		return 'string';
	}

	private static function getSingleElementByTagName($namespace,
		\DOMElement $element, $localName, $required = false)
	{
		$list = $element->getElementsByTagNameNS($namespace, $localName);

		if ($list->length > 1)
			throw new StructureSerializationException(
				'Invalid number of ' . $localName . ' nodes in ' .
				$element->nodeName . '. At most 1 expected. Got ' .
				$list->length);
		if ($list->length == 0)
		{
			if ($required)
				throw new StructureSerializationException(
					$localName . ' not found in ' . $element->nodeName);

			return null;
		}

		return $list->item(0);
	}

	private static function initializeDataTypeNodeNames()
	{
		self::$dataTypeNodeNames = [
			K::DATATYPE_BINARY => 'binary',
			K::DATATYPE_BOOLEAN => 'boolean',
			K::DATATYPE_TIMESTAMP => 'timestamp',
			K::DATATYPE_DATE => 'timestamp',
			K::DATATYPE_DATETIME => 'timestamp',
			K::DATATYPE_TIME => 'timestamp',
			K::DATATYPE_NUMBER => 'numeric',
			K::DATATYPE_FLOAT => 'numeric',
			K::DATATYPE_INTEGER => 'numeric',
			K::DATATYPE_NULL => 'null',
			K::DATATYPE_STRING => 'string',
			K::DATATYPE_UNDEFINED => 'string'
		];

		return self::$dataTypeNodeNames;
	}

	private static $dataTypeNodeNames;
}