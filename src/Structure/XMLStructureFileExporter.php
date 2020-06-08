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
use NoreSources\TypeConversion;
use NoreSources\SQL\Expression\Literal;
use NoreSources\SQL\Structure\XMLStructureFileConstants as K;

/**
 * Export Structure to a XML file following ns-xml SQL schema definition
 *
 * @see https://github.com/noresources/ns-xml
 */
class XMLStructureFileExporter implements StructureFileExporterInterface
{

	use XMLStructureFileTrait;

	public function __construct($version = '2.0')
	{
		$this->schemaVersion = new SemanticVersion($version);
	}

	public function exportStructureToFile(StructureElementInterface $structure, $filename)
	{
		return $this->exportStructure($structure)->save($filename);
	}

	/**
	 *
	 * @param StructureElementInterface $structure
	 * @return \DOMDocument
	 */
	public function exportStructure(StructureElementInterface $structure)
	{
		$context = new XMLStructureFileExporterContext();
		$namespaceURI = self::getXmlNamespaceURI($this->schemaVersion);
		$impl = new \DOMImplementation();
		$context->dom = $impl->createDocument($namespaceURI,
			K::XML_NAMESPACE_PREFIX . ':' . self::getXmlNodeName($structure, $this->schemaVersion));
		$context->identifiers = [];
		$context->parent = $context->dom->documentElement;

		self::traverseStructure($structure,
			function (StructureElementInterface $structure) use ($context) {
				$path = $structure->getPath();
				if (\strlen($path))
					$context->identifiers[$path] = $path . '-' . \uniqid();
			});

		$this->exportNode($structure, $context, $context->dom->documentElement);

		$context->dom->formatOutput = true;
		return $context->dom;
	}

	private function exportNode(StructureElementInterface $structure,
		XMLStructureFileExporterContext $context, \DOMNode $node)
	{
		$path = $structure->getPath();
		$namespaceURI = self::getXmlNamespaceURI($this->schemaVersion);
		$versionNumber = $this->schemaVersion->getIntegerValue();
		if ($structure instanceof DatasourceStructure)
		{
			if ($versionNumber < 20000)
				$node->setAttribute('version', '1.0');
		}
		else
		{
			$node->setAttribute('id', $context->identifiers[$path]);
			$node->setAttribute('name', $structure->getName());
		}

		if ($structure instanceof ColumnStructure)
		{
			$this->exportColumnNode($structure, $context, $node);
		}

		foreach ($structure as $name => $child)
		{
			$nodeName = self::getXmlNodeName($child, $this->schemaVersion);

			$n = $context->dom->createElementNS($namespaceURI, $nodeName);
			$this->exportNode($child, $context, $n);
			$node->appendChild($n);
		}

		if ($structure instanceof TableStructure)
		{
			$this->exportTableNode($structure, $context, $node);
		}
	}

	private function exportTableNode(TableStructure $structure,
		XMLStructureFileExporterContext $context, \DOMNode $node)
	{
		$namespaceURI = self::getXmlNamespaceURI($this->schemaVersion);
		$versionNumber = $this->schemaVersion->getIntegerValue();
		$constraints = $structure->getConstraints();

		foreach ($constraints as $index => $constraint)
		{
			$constraintNode = null;
			if ($constraint instanceof PrimaryKeyTableConstraint)
			{
				$constraintNode = $context->dom->createElementNS($namespaceURI, 'primarykey');
				foreach ($constraint as $column)
				{
					$c = $context->dom->createElementNS($namespaceURI, 'column');
					$c->setAttribute('name', $column->getName());
					$constraintNode->appendChild($c);
				}
			}
			elseif ($constraint instanceof ForeignKeyTableConstraint)
			{
				$constraintNode = $context->dom->createElementNS($namespaceURI, 'foreignkey');
				$ref = $context->dom->createElementNS($namespaceURI, 'reference');

				$reft = $context->dom->createElementNS($namespaceURI, 'tableref');
				$reft->setAttribute('id',
					$context->identifiers[$constraint->getForeignTable()
						->getPath()]);
				$ref->appendChild($reft);

				foreach ($constraint as $column => $reference)
				{
					$c = $context->dom->createElementNS($namespaceURI, 'column');
					$c->setAttribute('name', $column);
					$constraintNode->appendChild($c);

					$refc = $context->dom->createElementNS($namespaceURI, 'column');
					$refc->setAttribute('name', $reference);
					$ref->appendChild($refc);
				}

				$constraintNode->appendChild($ref);

				$actionsNode = $ref;
				if ($this->schemaVersion->getIntegerValue() >= 20000)
					$actionsNode = $context->dom->createElementNS($namespaceURI, 'actions');
				if ($constraint->onUpdate)
				{
					$a = $context->dom->createElementNS($namespaceURI, 'onupdate');
					$a->setAttribute('action', $constraint->onUpdate);
					$actionsNode->appendChild($a);
				}
				if ($constraint->onDelete)
				{
					$a = $context->dom->createElementNS($namespaceURI, 'ondelete');
					$a->setAttribute('action', $constraint->onDelete);
					$actionsNode->appendChild($a);
				}

				if ($actionsNode->hasChildNodes() && ($versionNumber >= 20000))
					$constraintNode->appendChild($actionsNode);
			}

			if (\strlen($constraint->constraintName))
				$constraintNode->setAttribute('name', $constraint->constraintName);
			elseif ($versionNumber < 20000)
			{
				$n = \preg_replace('/[^a-zA-Z0-9]/', '', $structure->getPath()) . $index;
				if (\strlen($n) > 64)
					$n = \substr($n, strlen($n) - 64);
				$constraintNode->setAttribute('name', $n);
			}

			$node->appendChild($constraintNode);
		}
	}

	private function exportColumnNode(ColumnStructure $structure,
		XMLStructureFileExporterContext $context, \DOMNode $node)
	{
		$namespaceURI = self::getXmlNamespaceURI($this->schemaVersion);
		$versionNumber = $this->schemaVersion->getIntegerValue();
		$dataTypeNode = $context->dom->createElementNS($namespaceURI, 'datatype');
		$dataType = $structure->getColumnProperty(K::COLUMN_DATA_TYPE);

		$flags = $structure->getColumnProperty(K::COLUMN_FLAGS);
		if (($flags & K::COLUMN_FLAG_NULLABLE) == 0)
		{
			if ($versionNumber >= 20000)
				$dataTypeNode->setAttribute('nullable', 'no');
			else
			{
				$notnull = $context->dom->createElementNS($namespaceURI, 'notnull');
				$node->appendChild($notnull);
			}
		}

		if ($structure->hasColumnProperty(K::COLUMN_DEFAULT_VALUE))
		{
			$defaultNode = $context->dom->createElementNS($namespaceURI, 'default');

			$value = $structure->getColumnProperty(K::COLUMN_DEFAULT_VALUE);
			$valueType = $dataType;
			if ($value instanceof Literal)
				$valueType = Literal::dataTypeFromValue($value->getValue());

			$defaultNodeValueNodeName = self::getDefaultNodeValueNodeName($dataType, $valueType,
				$this->schemaVersion);

			$defaultValue = '';
			if ($value instanceof Literal)
			{
				$v = $value->getValue();
				if ($v instanceof \DateTimeInterface)
					$defaultValue = $v->format(K::XML_DATETIME_FORMAT);
				else
					$defaultValue = TypeConversion::toString($v);

				switch ($dataType)
				{
					case K::DATATYPE_BINARY:
						$defaultValue = base64_encode($defaultValue);
					break;
					case K::DATATYPE_BOOLEAN:
						$defaultValue = ($defaultValue ? 'true' : 'false');
					break;
				}
			}

			$defaultValueNode = $context->dom->createElementNS($namespaceURI,
				$defaultNodeValueNodeName);
			if (\strlen(\strval($defaultValue)))
				$defaultValueNode->appendChild($context->dom->createTextNode($defaultValue));
			$defaultNode->appendChild($defaultValueNode);
			$node->appendChild($defaultNode);
		}

		$typeNodeName = self::getDataTypeNode($dataType, $this->schemaVersion);

		if (\strlen($typeNodeName))
		{
			$typeNode = $context->dom->createElementNS($namespaceURI, $typeNodeName);

			if ($dataType == K::DATATYPE_STRING)
			{
				if ($structure->hasColumnProperty(K::COLUMN_ENUMERATION))
				{
					$values = $structure->getColumnProperty(K::COLUMN_ENUMERATION);
					$enumerationNode = $context->dom->createElementNS($namespaceURI, 'enumeration');
					foreach ($values as $value)
					{
						$valueNode = $context->dom->createElementNS($namespaceURI, 'value');
						$valueNode->appendChild($context->dom->createTextNode($value));
						$enumerationNode->appendChild($valueNode);
					}
					$typeNode->appendChild($enumerationNode);
				}
			}
			elseif ($dataType & K::DATATYPE_NUMBER)
			{
				if ($flags & K::COLUMN_FLAG_UNSIGNED && ($versionNumber >= 20000))
					$typeNode->setAttribute('signed', 'no');
			}
			elseif ($dataType & K::DATATYPE_TIMESTAMP)
			{
				if ($versionNumber >= 20000)
				{
					if (($dataType & K::DATATYPE_TIMESTAMP) != K::DATATYPE_TIMESTAMP)
					{
						if ($dataType & K::DATATYPE_DATE)
						{
							$typeNode->appendChild(
								$context->dom->createElementNS($namespaceURI, 'date'));
						}
						if ($dataType & K::DATATYPE_TIME)
						{
							$time = $context->dom->createElementNS($namespaceURI, 'time');
							if (($dataType & K::DATATYPE_TIMEZONE) == K::DATATYPE_TIMEZONE)
							{
								$time->setAttribute('timezone', 'yes');
							}
							$typeNode->appendChild($time);
						}
					}
				}
				else
				{
					$mode = 'datetime';
					if (($dataType & K::DATATYPE_DATETIME) == K::DATATYPE_DATE)
						$mode = 'date';
					if (($dataType & K::DATATYPE_DATETIME) == K::DATATYPE_TIME)
						$mode = 'time';

					$typeNode->setAttribute('mode', $mode);
					if (($dataType & K::DATATYPE_TIMEZONE) == K::DATATYPE_TIMEZONE)
					{
						$typeNode->setAttribute('timezone', 'yes');
					}
				}
			}

			if ($structure->hasColumnProperty(K::COLUMN_LENGTH))
			{
				$typeNode->setAttribute('length', $structure->getColumnProperty(K::COLUMN_LENGTH));
			}

			if ($structure->hasColumnProperty(K::COLUMN_FRACTION_SCALE))
			{
				$n = ($versionNumber >= 20000) ? 'scale' : 'decimals';
				$typeNode->setAttribute($n, $structure->getColumnProperty(K::COLUMN_FRACTION_SCALE));
			}

			$dataTypeNode->appendChild($typeNode);
		}

		if ($dataTypeNode->hasAttributes() || $dataTypeNode->hasChildNodes())
		{
			$node->appendChild($dataTypeNode);
		}
	}

	private static function traverseStructure(StructureElementInterface $structure, $callable)
	{
		call_user_func($callable, $structure);
		foreach ($structure as $name => $child)
		{
			self::traverseStructure($child, $callable);
		}
	}

	/**
	 *
	 * @var SemanticVersion
	 */
	private $schemaVersion;
}

class XMLStructureFileExporterContext
{

	/**
	 *
	 * @var \DOMDocument
	 */
	public $dom;

	/**
	 * Canonical name <-> XML ID map
	 *
	 * @var string[]
	 */
	public $identifiers;
}