<?php
/**
 * Copyright © 2020 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure\Exporter;

use NoreSources\SemanticVersion;
use NoreSources\Container\Container;
use NoreSources\Expression\Value;
use NoreSources\SQL\DataTypeProviderInterface;
use NoreSources\SQL\DBMS\Reference\ReferencePlatform;
use NoreSources\SQL\Structure\ColumnStructure;
use NoreSources\SQL\Structure\DatasourceStructure;
use NoreSources\SQL\Structure\ForeignKeyTableConstraint;
use NoreSources\SQL\Structure\Identifier;
use NoreSources\SQL\Structure\IndexStructure;
use NoreSources\SQL\Structure\KeyTableConstraintInterface;
use NoreSources\SQL\Structure\StructureElementContainerInterface;
use NoreSources\SQL\Structure\StructureElementInterface;
use NoreSources\SQL\Structure\StructureResolver;
use NoreSources\SQL\Structure\TableStructure;
use NoreSources\SQL\Structure\XMLStructureFileConstants as K;
use NoreSources\SQL\Structure\Traits\XMLStructureFileTrait;
use NoreSources\SQL\Syntax\Evaluator;
use NoreSources\SQL\Syntax\Keyword;
use NoreSources\Type\TypeConversion;
use NoreSources\Type\TypeDescription;

/**
 * Export Structure to a XML file following ns-xml SQL schema definition
 *
 * @see https://github.com/noresources/ns-xml
 */
class XMLStructureFileExporter implements
	StructureFileExporterInterface
{

	use XMLStructureFileTrait;

	/**
	 *
	 * @param string $version
	 *        	Schema version
	 */
	public function __construct($version = '2.0')
	{
		$this->schemaVersion = new SemanticVersion($version);
		$this->setIdentifierGenerator(
			[
				self::class,
				'defaultIdentifierGenerator'
			]);
	}

	public function exportStructureToFile(
		StructureElementInterface $structure, $filename)
	{
		return $this->exportStructure($structure)->save($filename);
	}

	/**
	 *
	 * @param StructureElementInterface $structure
	 * @return \DOMDocument
	 */
	public function exportStructure(
		StructureElementInterface $structure)
	{
		$context = new XMLStructureFileExporterContext();
		$context->resolver = new StructureResolver($structure);
		$namespaceURI = self::getXmlNamespaceURI($this->schemaVersion);
		$impl = new \DOMImplementation();
		$context->dom = $impl->createDocument($namespaceURI,
			K::XML_NAMESPACE_PREFIX . ':' .
			self::getXmlNodeName($structure, $this->schemaVersion));
		$context->parent = $context->dom->documentElement;
		$context->identifiers = [];

		self::traverseStructure($structure,
			function ($e) use ($context) {
				$a = $this->defaultIdentifierGenerator($e);
				$b = \call_user_func($this->identifierGenerator, $e);
				$context->identifiers[$a] = $b;
			});

		$this->exportNode($structure, $context,
			$context->dom->documentElement);

		$context->dom->formatOutput = true;
		return $context->dom;
	}

	/**
	 *
	 * @param callable $generator
	 */
	public function setIdentifierGenerator($generator)
	{
		$this->identifierGenerator = $generator;
	}

	public static function defaultIdentifierGenerator(
		StructureElementInterface $element)
	{
		$path = \strval($element->getIdentifier());
		if (empty($path))
		{
			$key = $element->getElementKey();
			$p = $element->getParentElement();
			if ($p)
			{
				/**
				 *
				 * @var Identifier $id
				 */
				$id = $p->getIdentifier();
				$id->append($key);
				$path = \strval($id);
			}
			else
				$path = $key;
		}

		return \str_replace('=', '', \base64_encode($path));
	}

	private function exportNode(StructureElementInterface $structure,
		XMLStructureFileExporterContext $context, \DOMNode $node)
	{
		$context->resolver->setPivot($structure);

		$id = $this->defaultIdentifierGenerator($structure);
		$id = $context->identifiers[$id];
		$namespaceURI = self::getXmlNamespaceURI($this->schemaVersion);
		$versionNumber = $this->schemaVersion->getIntegerValue();
		if ($structure instanceof DatasourceStructure)
		{
			if ($versionNumber < 20000)
				$node->setAttribute('version', '1.0');
		}
		else
		{
			$name = $structure->getName();
			if (!empty($name))
				$node->setAttribute('name', $name);
			if ($structure instanceof StructureElementContainerInterface)
				$node->setAttribute('id', $id);
		}

		if ($structure instanceof ColumnStructure)
			$this->exportColumnNode($structure, $context, $node);
		elseif ($structure instanceof TableStructure)
			$this->exportTableNode($structure, $context, $node);
		elseif ($structure instanceof IndexStructure)
			$this->exportIndexNode($structure, $context, $node);
		elseif ($structure instanceof KeyTableConstraintInterface)
			$this->exportKeyTableConstraintNode($structure, $context,
				$node);
		elseif ($structure instanceof ForeignKeyTableConstraint)
			$this->exportForeignKeyTableConstraintNode($structure,
				$context, $node);

		if ($structure instanceof StructureElementContainerInterface)
		{
			foreach ($structure as $name => $child)
			{
				$nodeName = self::getXmlNodeName($child,
					$this->schemaVersion);

				$n = $context->dom->createElementNS($namespaceURI,
					$nodeName);
				$node->appendChild($n);
				$this->exportNode($child, $context, $n);
			}
		}
	}

	private function exportIndexNode(IndexStructure $structure,
		XMLStructureFileExporterContext $context, \DOMElement $node)
	{
		$namespaceURI = self::getXmlNamespaceURI($this->schemaVersion);
		$versionNumber = $this->schemaVersion->getIntegerValue();

		$tableref = $context->dom->createElementNS($namespaceURI,
			'tableref');
		$indexTable = $structure->getParentElement();
		$indexTableId = $this->defaultIdentifierGenerator($indexTable);
		$tableref->setAttribute('id',
			$context->identifiers[$indexTableId]);
		$node->appendChild($tableref);

		foreach ($structure->getColumns() as $column)
		{
			$c = $context->dom->createElementNS($namespaceURI, 'column');
			$c->setAttribute('name', $column);
			$node->appendChild($c);
		}

		$gp = $node->parentNode->parentNode;
		if ($gp)
			$gp->appendChild($node);
	}

	private function exportKeyTableConstraintNode(
		KeyTableConstraintInterface $structure,
		XMLStructureFileExporterContext $context, \DOMElement $node)
	{
		$namespaceURI = self::getXmlNamespaceURI($this->schemaVersion);
		$versionNumber = $this->schemaVersion->getIntegerValue();

		foreach ($structure->getColumns() as $column)
		{
			$c = $context->dom->createElementNS($namespaceURI, 'column');
			$c->setAttribute('name', $column);
			$node->appendChild($c);
		}
	}

	private function exportForeignKeyTableConstraintNode(
		ForeignKeyTableConstraint $constraint,
		XMLStructureFileExporterContext $context, \DOMElement $node)
	{
		$namespaceURI = self::getXmlNamespaceURI($this->schemaVersion);
		$versionNumber = $this->schemaVersion->getIntegerValue();

		$ref = $context->dom->createElementNS($namespaceURI, 'reference');

		$reft = $context->dom->createElementNS($namespaceURI, 'tableref');
		$ft = $constraint->getForeignTable();
		$ft = $context->resolver->findTable(\strval($ft));
		$ftid = $this->defaultIdentifierGenerator($ft);
		$reft->setAttribute('id', $context->identifiers[$ftid]);
		$ref->appendChild($reft);

		foreach ($constraint->getColumns() as $column => $reference)
		{
			$c = $context->dom->createElementNS($namespaceURI, 'column');
			$c->setAttribute('name', $column);
			$node->appendChild($c);

			$refc = $context->dom->createElementNS($namespaceURI,
				'column');
			$refc->setAttribute('name', $reference);
			$ref->appendChild($refc);
		}

		$node->appendChild($ref);

		$actionsNode = $ref;
		if ($this->schemaVersion->getIntegerValue() >= 20000)
			$actionsNode = $context->dom->createElementNS($namespaceURI,
				'actions');
		if ($constraint->getEvents()->has(K::EVENT_UPDATE))
		{
			$a = $context->dom->createElementNS($namespaceURI,
				'onupdate');
			$a->setAttribute('action',
				$constraint->getEvents()
					->get(K::EVENT_UPDATE));
			$actionsNode->appendChild($a);
		}
		if ($constraint->getEvents()->has(K::EVENT_DELETE))
		{
			$a = $context->dom->createElementNS($namespaceURI,
				'ondelete');
			$a->setAttribute('action',
				$constraint->getEvents()
					->get(K::EVENT_DELETE));
			$actionsNode->appendChild($a);
		}

		if ($actionsNode->hasChildNodes() && ($versionNumber >= 20000))
			$node->appendChild($actionsNode);
	}

	private function exportTableNode(TableStructure $structure,
		XMLStructureFileExporterContext $context, \DOMElement $node)
	{
		$namespaceURI = self::getXmlNamespaceURI($this->schemaVersion);
		$versionNumber = $this->schemaVersion->getIntegerValue();
		$constraints = $structure->getConstraints();

		return;

		foreach ($constraints as $index => $constraint)
		{

			$node = null;
			$nodeName = self::getXmlNodeName($constraint,
				$this->schemaVersion);

			if ($constraint instanceof KeyTableConstraintInterface)
			{
				$node = $context->dom->createElementNS($namespaceURI,
					$nodeName);

				foreach ($constraint as $column)
				{
					$c = $context->dom->createElementNS($namespaceURI,
						'column');
					$c->setAttribute('name', $column);
					$node->appendChild($c);
				}
			}
			elseif ($constraint instanceof ForeignKeyTableConstraint)
			{
				$node = $context->dom->createElementNS($namespaceURI,
					'foreignkey');
			}

			if ($constraint->getName())
				$node->setAttribute('name', $constraint->getName());
			elseif ($versionNumber < 20000)
			{
				$n = \preg_replace('/[^a-zA-Z0-9]/', '',
					\strval($structure->getIdentifier())) . $index;
				if (\strlen($n) > 64)
					$n = \substr($n, strlen($n) - 64);
				$node->setAttribute('name', $n);
			}

			$node->appendChild($node);
		}
	}

	private function exportColumnNode(ColumnStructure $structure,
		XMLStructureFileExporterContext $context, \DOMElement $node)
	{
		$namespaceURI = self::getXmlNamespaceURI($this->schemaVersion);
		$versionNumber = $this->schemaVersion->getIntegerValue();
		$dataTypeNode = $context->dom->createElementNS($namespaceURI,
			'datatype');
		$dataType = $structure->get(K::COLUMN_DATA_TYPE);
		$flags = $structure->get(K::COLUMN_FLAGS);

		if (!($dataType & K::DATATYPE_NULL))
		{
			if ($versionNumber >= 20000)
				$dataTypeNode->setAttribute('nullable', 'no');
			else
			{
				$notnull = $context->dom->createElementNS($namespaceURI,
					'notnull');
				$node->appendChild($notnull);
			}
		}

		$dataType &= ~K::DATATYPE_NULL;

		if ($structure->has(K::COLUMN_DEFAULT_VALUE))
		{
			$defaultNode = $context->dom->createElementNS($namespaceURI,
				'default');

			$value = $structure->get(K::COLUMN_DEFAULT_VALUE);
			$valueType = $dataType;
			if ($value instanceof DataTypeProviderInterface)
				$valueType = $value->getDataType();
			elseif ($value instanceof Value)
				$valueType = Evaluator::getInstance()->getDataType(
					$value->getValue());

			$defaultNodeValueNodeName = self::getDefaultNodeValueNodeName(
				$dataType, $valueType, $this->schemaVersion);

			$defaultValue = '';
			if ($value instanceof Value)
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
			elseif ($value instanceof Keyword)
			{
				$k = $value->getKeyword();
				switch ($k)
				{
					case K::KEYWORD_CURRENT_TIMESTAMP:
						$valueType = K::DATATYPE_TIMESTAMP;
						$defaultValue = null;
					break;
					case K::KEYWORD_TRUE:
						$valueType = K::DATATYPE_BOOLEAN;
						$defaultValue = 'true';
					break;
					case K::KEYWORD_FALSE:
						$valueType = K::DATATYPE_BOOLEAN;
						$defaultValue = 'false';
					break;
					case K::KEYWORD_NULL:
						$valueType = K::DATATYPE_NULL;
						$defaultValue = null;
					break;
					default:
						$r = new ReferencePlatform();
						$s = $r->getKeyword($k);
						throw new \Exception(
							'Unsupported keyword ' . $s . ' (' . $k . ')');
					break;
				}
			}
			else
				throw new \Exception(
					'Unexpected default value type ' .
					TypeDescription::getName($value) . ' for column ' .
					$structure->getName());

			$defaultValueNode = $context->dom->createElementNS(
				$namespaceURI, $defaultNodeValueNodeName);
			if (\strlen(\strval($defaultValue)))
				$defaultValueNode->appendChild(
					$context->dom->createTextNode($defaultValue));
			$defaultNode->appendChild($defaultValueNode);
			$node->appendChild($defaultNode);
		}

		$typeNodeName = self::getDataTypeNode($dataType,
			$this->schemaVersion);

		if (\strlen($typeNodeName))
		{
			$typeNode = $context->dom->createElementNS($namespaceURI,
				$typeNodeName);

			if (($length = Container::keyValue($structure,
				K::COLUMN_LENGTH, 0)))
			{
				$typeNode->setAttribute("length", $length);
			}

			if ($dataType == K::DATATYPE_STRING)
			{
				if ($structure->has(K::COLUMN_ENUMERATION))
				{
					$values = $structure->get(K::COLUMN_ENUMERATION);
					$enumerationNode = $context->dom->createElementNS(
						$namespaceURI, 'enumeration');
					foreach ($values as $value)
					{
						$valueNode = $context->dom->createElementNS(
							$namespaceURI, 'value');
						$valueNode->appendChild(
							$context->dom->createTextNode(
								$value->getValue()));
						$enumerationNode->appendChild($valueNode);
					}
					$typeNode->appendChild($enumerationNode);
				}
			}
			elseif ($dataType & K::DATATYPE_NUMBER)
			{
				if ($flags & K::COLUMN_FLAG_AUTO_INCREMENT)
					$typeNode->setAttribute('autoincrement', 'yes');

				if ($flags & K::COLUMN_FLAG_UNSIGNED &&
					($versionNumber >= 20000))
					$typeNode->setAttribute('signed', 'no');

				if (($scale = Container::keyValue($structure,
					K::COLUMN_FRACTION_SCALE)))
				{
					$scaleAttribute = ($versionNumber < 20000) ? 'decimals' : 'scale';
					$typeNode->setAttribute($scaleAttribute, $scale);
				}
			}
			elseif ($dataType & K::DATATYPE_TIMESTAMP)
			{
				if ($versionNumber >= 20000)
				{
					if (($dataType & K::DATATYPE_TIMESTAMP) !=
						K::DATATYPE_TIMESTAMP)
					{
						if ($dataType & K::DATATYPE_DATE)
						{
							$typeNode->appendChild(
								$context->dom->createElementNS(
									$namespaceURI, 'date'));
						}
						if ($dataType & K::DATATYPE_TIME)
						{
							$time = $context->dom->createElementNS(
								$namespaceURI, 'time');
							if (($dataType & K::DATATYPE_TIMEZONE) ==
								K::DATATYPE_TIMEZONE)
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
					if (($dataType & K::DATATYPE_DATETIME) ==
						K::DATATYPE_DATE)
						$mode = 'date';
					if (($dataType & K::DATATYPE_DATETIME) ==
						K::DATATYPE_TIME)
						$mode = 'time';

					$typeNode->setAttribute('mode', $mode);
					if (($dataType & K::DATATYPE_TIMEZONE) ==
						K::DATATYPE_TIMEZONE)
					{
						$typeNode->setAttribute('timezone', 'yes');
					}
				}
			}

			if ($structure->has(K::COLUMN_LENGTH))
			{
				$typeNode->setAttribute('length',
					$structure->get(K::COLUMN_LENGTH));
			}

			if ($structure->has(K::COLUMN_FRACTION_SCALE))
			{
				$n = ($versionNumber >= 20000) ? 'scale' : 'decimals';
				$typeNode->setAttribute($n,
					$structure->get(K::COLUMN_FRACTION_SCALE));
			}

			$dataTypeNode->appendChild($typeNode);
		}

		if ($dataTypeNode->hasAttributes() ||
			$dataTypeNode->hasChildNodes())
		{
			$node->appendChild($dataTypeNode);
		}
	}

	private static function traverseStructure(
		StructureElementInterface $structure, $callable)
	{
		call_user_func($callable, $structure);
		foreach ($structure as $name => $child)
		{
			if ($child instanceof StructureElementInterface)
				self::traverseStructure($child, $callable);
		}
	}

	/**
	 *
	 * @var SemanticVersion
	 */
	private $schemaVersion;

	/**
	 *
	 * @var callable
	 */
	private $identifierGenerator;
}

class XMLStructureFileExporterContext
{

	/**
	 *
	 * @var \DOMDocument
	 */
	public $dom;

	/**
	 *
	 * @var StructureResolver
	 */
	public $resolver;

	/**
	 *
	 * @var array <string, string>
	 */
	public $identifiers;
}