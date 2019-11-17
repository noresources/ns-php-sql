<?php
namespace NoreSources\SQL;

use NoreSources as ns;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\ExpressionEvaluator as X;

/**
 */
abstract class StructureSerializer implements \Serializable
{

	/**
	 * Unserialize from file
	 *
	 * @param string $filename
	 */
	public function userializeFromFile($filename)
	{
		return $this->unserialize(file_get_contents($filename));
	}

	/**
	 * Serialize to file
	 *
	 * @param string $filename
	 */
	public function serializeToFile($filename)
	{
		return file_put_contents($filename, $this->serialize());
	}

	public function __construct(StructureElement $element = null)
	{
		$this->structureElement = $element;
	}

	/**
	 *
	 * @property-read StructureElement $structureElement
	 * @param string $member
	 * @throws \InvalidArgumentException
	 * @return \NoreSources\SQL\StructureElement
	 */
	public function __get($member)
	{
		if ($member == 'structureElement')
		{
			return $this->structureElement;
		}

		throw new \InvalidArgumentException($member);
	}

	/**
	 *
	 * @var StructureElement
	 */
	protected $structureElement;
}

class JSONStructureSerializer extends StructureSerializer implements \JsonSerializable
{

	/**
	 *
	 * @var integer
	 */
	public $jsonSerializeFlags;

	public function __construct(StructureElement $structure, $flags = 0)
	{
		parent::__construct($structure);
		$this->jsonSerializeFlags = $flags;
	}

	public function unserialize($serialized)
	{
		$json = json_decode($serialized);
		if (!is_object($json))
			throw new StructureException('Invalid JSON data');

		throw new \Exception('Not implemented');
	}

	public function serialize()
	{
		return json_encode($this->jsonSerialize(), $this->jsonSerializeFlags);
	}

	public function jsonSerialize()
	{
		if ($this->structureElement instanceof DatasourceStructure)
		{
			return $this->serializeDatasource($this->structureElement);
		}
		elseif ($this->structureElement instanceof TableSetStructure)
		{
			return $this->serializeTableSet($this->structureElement);
		}
		elseif ($this->structureElement instanceof TableStructure)
		{
			return $this->serializeTable($this->structureElement);
		}
		elseif ($this->structureElement instanceof TableColumnStructure)
		{
			return $this->serializeTableColumn($this->structureElement);
		}

		return array();
	}

	private function serializeDatasource(DatasourceStructure $structure)
	{
		$properties = [
			'name' => $structure->getName(),
			'kind' => 'datasource',
			'tablesets' => []
		];

		foreach ($structure as $tableName => $table)
		{
			$properties['tablesets'][$tableName] = $this->serializeTableSet($table);
		}

		return $properties;
	}

	private function serializeTableSet(TableSetStructure $structure)
	{
		$properties = [
			'tables' => []
		];

		foreach ($structure as $tableName => $table)
		{
			$properties['tables'][$tableName] = $this->serializeTable($table);
		}

		if (!($structure->parent() instanceof DatasourceStructure))
		{
			$properties = array_merge([
				'name' => $structure->getName(),
				'kind' => 'tableset'
			], $properties);
		}

		return $properties;
	}

	private function serializeTable(TableStructure $structure)
	{
		$properties = [
			'columns' => []
		];

		foreach ($structure as $columnName => $column)
		{
			$properties['columns'][$columnName] = $this->serializeTableColumn($column);
		}

		if (!($structure->parent() instanceof TableSetStructure))
		{
			$properties = array_merge([
				'name' => $structure->getName(),
				'kind' => 'table'
			], $properties);
		}

		return $properties;
	}

	private function serializeTableColumn(TableColumnStructure $structure)
	{
		$properties = [];
		foreach ($structure->getColumnProperties() as $key => $value)
		{
			$properties[$key] = $value;
		}
		if (!($structure->parent() instanceof TableStructure))
		{
			$properties = array_merge([
				'name' => $structure->getName(),
				'kind' => 'column'
			], $properties);
		}

		return $properties;
	}
}

class XMLStructureSerializer extends StructureSerializer
{

	public function __construct(StructureElement $element = null)
	{
		parent::__construct($element);
		$this->schemaVersion = new ns\SemanticVersion('2.0.0');
		$this->schemaNamespaceURI = K::XML_NAMESPACE_BASEURI . '/2.0';
	}

	public function serialize()
	{
		if ($this->structureElement instanceof StructureElement)
			throw new StructureException('Nothing to serialize');

		$impl = new \DOMImplementation();
		$document = $impl->createDocument($this->schemaNamespaceURI,
			self::XSLT_NAMESPACE_PREFIX . ':' . self::getXmlNodeName($this->structureElement));

		/**
		 *
		 * @todo
		 */

		return $document->saveXML();
	}

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
		$this->foreignKeys = new \ArrayObject();
		$this->identifiedElements = new \ArrayObject();

		$document = null;

		if ($serialized instanceof \DOMDocument)
			$document = $serialized;
		else
		{
			$document = new \DOMDocument('1.0', 'utf-8');
			$document->loadXML($serialized, LIBXML_XINCLUDE);
		}

		$document->xinclude();

		$xpath = new \DOMXPath($document);
		$validDocument = false;
		foreach ($xpath->query('namespace::*', $document->documentElement) as $node)
		{
			if (strpos($node->nodeValue, K::XML_NAMESPACE_BASEURI) === 0)
			{
				$this->schemaNamespaceURI = $node->nodeValue;

				$validDocument = true;
				$version = trim(trim(substr($node->nodeValue, strlen(K::XML_NAMESPACE_BASEURI))),
					'/');
				if (strlen($version) == 0)
					$version = '1.0.0';
				$this->schemaVersion = new ns\SemanticVersion($version);
			}
		}

		$versionNumber = $this->schemaVersion->getIntegerValue();

		if (!$validDocument)
			throw new StructureException('Invalid XML document. Schema namespace not found');

		if ($document->documentElement->localName == 'datasource')
		{
			$this->structureElement = new DatasourceStructure(
				$document->documentElement->getAttribute('name'));
			$this->unserializeDatasource($this->structureElement, $document->documentElement);
		}
		elseif (($schemaVersion < 20000 && $document->documentElement->localName == 'database') ||
			($schemaVersion >= 20000 && $document->documentElement->localName == 'tableset'))
		{
			$this->structureElement = new TableSetStructure(
				$document->documentElement->getAttribute('name'));
			$this->unserializeTableset($this->structureElement, $document->documentElement);
		}
		elseif ($document->documentElement->localName == 'table')
		{
			$this->structureElement = new TableStructure(
				$document->documentElement->getAttribute('name'));
			$this->unserializeTable($this->structureElement, $document->documentElement);
		}
		elseif ($document->documentElement->localName == 'column')
		{
			$this->structureElement = new TableColumnStructure(
				$document->documentElement->getAttribute('name'));
			$this->unserializeTableColumn($this->structureElement, $document->documentElement);
		}

		$this->userializePostprocess($document);
	}

	private function unserializeDatasource(DatasourceStructure $structure, \DOMNode $node)
	{
		if ($node->hasAttribute('id'))
			$this->identifiedElements->offsetSet($node->getAttribute('id'), $structure);

		$xpath = new \DOMXPath($node->ownerDocument);
		$xpath->registerNamespace(K::XML_NAMESPACE_PREFIX, $this->schemaNamespaceURI);

		$nodeName = ($this->schemaVersion->getIntegerValue() < 20000) ? 'database' : 'tableset';
		$nodeName = K::XML_NAMESPACE_PREFIX . ':' . $nodeName;
		$tablesetNodes = $xpath->query($nodeName);
		foreach ($tablesetNodes as $tablesetNode)
		{
			$tableset = new TableSetStructure($structure, $tablesetNode->getAttribute('name'));
			$structure->appendChild($tableset);
			$this->unserializeTableSet($tableset, $tablesetNode);
		}
	}

	private function unserializeTableSet(TableSetStructure $structure, \DOMNode $node)
	{
		if ($node->hasAttribute('id'))
			$this->identifiedElements->offsetSet($node->getAttribute('id'), $structure);

		$xpath = new \DOMXPath($node->ownerDocument);
		$xpath->registerNamespace(K::XML_NAMESPACE_PREFIX, $this->schemaNamespaceURI);

		$nodeName = K::XML_NAMESPACE_PREFIX . ':' . 'table';
		$tableNodes = $xpath->query($nodeName, $node);
		foreach ($tableNodes as $tableNode)
		{
			$table = new TableStructure($structure, $tableNode->getAttribute('name'));
			$structure->appendChild($table);
			$this->unserializeTable($table, $tableNode);
		}
	}

	private function unserializeTable(TableStructure $structure, \DOMNode $node)
	{
		if ($node->hasAttribute('id'))
			$this->identifiedElements->offsetSet($node->getAttribute('id'), $structure);

		$xpath = new \DOMXPath($node->ownerDocument);
		$xpath->registerNamespace(K::XML_NAMESPACE_PREFIX, $this->schemaNamespaceURI);

		$columnNodeName = K::XML_NAMESPACE_PREFIX . ':' . 'column';
		$columnNodes = $xpath->query($columnNodeName, $node);
		foreach ($columnNodes as $columnNode)
		{
			$column = new TableColumnStructure($structure, $columnNode->getAttribute('name'));
			$structure->appendChild($column);
			$this->unserializeTableColumn($column, $columnNode);
		}

		$pkNode = self::getSingleElementByTagName($this->schemaNamespaceURI, $node, 'primarykey');
		if ($pkNode instanceof \DOMElement)
		{
			$constraint = new PrimaryKeyTableConstraint();
			$constraint->constraintName = $pkNode->getAttribute('name');

			$columnNodes = $xpath->query($columnNodeName, $pkNode);
			foreach ($columnNodes as $columnNode)
			{
				$name = $columnNode->getAttribute('name');
				if (!$structure->offsetExists($name))
				{
					throw new StructureException('Invalid primary column "' . $name . '"',
						$structure);
				}
				$constraint->offsetSet($name, $structure->offsetGet($name));
			}

			$structure->addConstraint($constraint);
		}

		$fkNodes = $node->getElementsByTagNameNS($this->schemaNamespaceURI, 'foreignkey');
		foreach ($fkNodes as $fkNode)
		{
			$this->foreignKeys->append([
				'table' => $structure,
				'node' => $fkNode
			]);
		}
	}

	private function unserializeTableColumn(TableColumnStructure $structure, \DOMNode $node)
	{
		if ($node->hasAttribute('id'))
			$this->identifiedElements->offsetSet($node->getAttribute('id'), $structure);

		$notNullNode = self::getSingleElementByTagName($this->schemaNamespaceURI, $node, 'notnull');
		if ($notNullNode instanceof \DOMNode)
		{
			$structure->setColumnProperty(K::COLUMN_PROPERTY_ACCEPT_NULL, false);
		}

		$type = K::DATATYPE_UNDEFINED;
		$typeNode = null;

		$dataTypeNode = self::getSingleElementByTagName($this->schemaNamespaceURI, $node, 'datatype');
		if ($dataTypeNode instanceof \DOMElement)
		{
			$a = [
				'binary' => K::DATATYPE_BINARY,
				'boolean' => K::DATATYPE_BOOLEAN,
				'numeric' => K::DATATYPE_NUMBER,
				'timestamp' => K::DATATYPE_TIMESTAMP,
				'string' => K::DATATYPE_STRING
			];

			foreach ($a as $k => $v)
			{
				$typeNode = self::getSingleElementByTagName($this->schemaNamespaceURI, $dataTypeNode,
					$k);
				if ($typeNode instanceof \DOMElement)
				{
					$type = $v;
					break;
				}
			}
		}

		if ($type & K::DATATYPE_TIMESTAMP)
		{
			if ($this->schemaVersion->getIntegerValue() < 20000)
			{
				if (!$typeNode->hasAttribute('timezone'))
				{
					$type &= ~K::DATATYPE_TIMEZONE;
				}

				if ($typeNode->hasAttribute('type'))
				{
					$timestampType = $typeNode->getAttribute('type');
					$type &= ~K::DATATYPE_DATETIME;
					if ($timestampType == 'date')
						$type |= K::DATATYPE_DATE;
					elseif ($timestampType == 'time')
						$type |= K::DATATYPE_TIME;
					elseif ($timestampType == 'datetime')
						$type |= K::DATATYPE_DATETIME;
				}
			}
			else
			{
				$dateNode = self::getSingleElementByTagName($this->schemaNamespaceURI, $typeNode,
					'date');
				$timeNode = self::getSingleElementByTagName($this->schemaNamespaceURI, $typeNode,
					'time');
				if ($dateNode instanceof \DOMElement || $timeNode instanceof \DOMElement)
				{
					$type = 0;
				}

				if ($dateNode instanceof \DOMElement)
					$type |= K::DATATYPE_DATE;
				if ($timeNode instanceof \DOMElement)
				{
					$type |= K::DATATYPE_TIME;
					if ($timeNode->hasAttribute('timezone'))
						$type |= K::DATATYPE_TIMEZONE;
				}
			}
		}
		elseif ($type & K::DATATYPE_NUMBER)
		{
			$type = K::DATATYPE_INTEGER;
			if ($typeNode->hasAttribute('autoincrement'))
			{
				$structure->setColumnProperty(K::COLUMN_PROPERTY_AUTO_INCREMENT, true);
			}
			if ($typeNode->hasAttribute('length'))
			{
				$structure->setColumnProperty(K::COLUMN_PROPERTY_DATA_SIZE,
					intval($typeNode->getAttribute('length')));
			}
			if ($typeNode->hasAttribute('decimals'))
			{
				$count = intval($typeNode->getAttribute('decimals'));
				$structure->setColumnProperty(K::COLUMN_PROPERTY_FRACTION_DIGIT_COUNT, $count);
				if ($count > 0)
				{
					$type = K::DATATYPE_FLOAT;
				}
			}
		}

		if ($type != K::DATATYPE_UNDEFINED)
			$structure->setColumnProperty(K::COLUMN_PROPERTY_DATA_TYPE, $type);

		$defaultNode = self::getSingleElementByTagName($this->schemaNamespaceURI, $node, 'default');
		if ($defaultNode instanceof \DOMElement)
		{
			$nodeNames = [
				'integer',
				'boolean',
				'string',
				'null',
				'number',
				'base64Binary',
				'hexBinary'
			];

			if ($this->schemaVersion->getIntegerValue() < 20000)
				$nodeNames[] = 'datetime';
			else
			{
				$nodeNames[] = 'timestamp';
				$nodeNames[] = 'now';
			}

			foreach ($nodeNames as $name)
			{
				$valueNode = self::getSingleElementByTagName($this->schemaNamespaceURI, $defaultNode,
					$name);
				if (!($valueNode instanceof \DOMNode))
					continue;

				$value = $valueNode->nodeValue;
				$valueType = K::DATATYPE_UNDEFINED;
				switch ($name)
				{
					case 'integer':
						$value = intval($value);
						$valueType = K::DATATYPE_INTEGER;
					break;
					case 'boolean':
						$value = ($value == 'true' ? true : false);
						$valueType = K::DATATYPE_BOOLEAN;
					break;
					case 'timestamp':
					case 'now':
					case 'datetime':
						$valueType = K::DATATYPE_TIMESTAMP; // deprecated
						if (strlen($value))
							$value = \DateTime::createFromFormat(\DateTime::ISO8601, $value);
						else
							$value = new KeywordExpression(K::KEYWORD_CURRENT_TIMESTAMP);
					break;
					case 'null':
						$valueType = K::DATATYPE_NULL;
						$value = null;
					break;
					case 'number':
						$ivalue = intval($value);
						$value = floatval($value);
						$valueType = K::DATATYPE_FLOAT;
						if ($ivalue == $value)
						{
							$valueType = K::DATATYPE_INTEGER;
							$value = $ivalue;
						}
					break;
					case 'base64Binary':
						$valueType = K::DATATYPE_BINARY;
						$value = base64_decode($value);
					break;
					case 'hexBinary':
						$valueType = K::DATATYPE_BINARY;
						$value = hex2bin($value);
					break;
					default:
					break;
				}

				if (!($value instanceof Expression))
				{
					$value = X::literal($value, $valueType);
				}

				$structure->setColumnProperty(K::COLUMN_PROPERTY_DEFAULT_VALUE, $value);

				break;
			} // for each default value type
		} // default node
	}

	public function getXmlNodeName(StructureElement $element)
	{
		if ($element instanceof DatasourceStructure)
			return 'datasource';
		elseif ($element instanceof TableSetStructure)
		{
			if ($this->schemaVersion->getIntegerValue() < 20000)
				return 'database';
			return 'tableset';
		}
		elseif ($element instanceof TableStructure)
			return 'table';
		elseif ($element instanceof TableColumnStructure)
			return 'column';
		throw new \InvalidArgumentException();
	}

	private static function getSingleElementByTagName($namespace, \DOMElement $element, $localName,
		$required = false)
	{
		$list = $element->getElementsByTagNameNS($namespace, $localName);

		if ($list->length > 1)
			throw new StructureException(
				'Invalid number of ' . $localName . ' nodes. At most 1 expected');
		if ($list->length == 0)
		{
			if ($required)
				throw new StructureException($localName . ' not found');

			return null;
		}

		return $list->item(0);
	}

	/**
	 * Resolve foreign key constaints
	 *
	 * @throws StructureException
	 */
	private function userializePostprocess(\DOMDocument $document)
	{
		$resolver = new StructureResolver(null);

		$xpath = new \DOMXPath($document);
		$xpath->registerNamespace(K::XML_NAMESPACE_PREFIX, $this->schemaNamespaceURI);

		$columnNodeName = K::XML_NAMESPACE_PREFIX . ':' . 'column';

		foreach ($this->foreignKeys as $entry)
		{
			$structure = $entry['table'];
			$fkNode = $entry['node'];
			$resolver->setPivot($structure);

			$referenceNode = self::getSingleElementByTagName($this->schemaNamespaceURI, $fkNode,
				'reference', true);
			$columnNodes = $xpath->query($columnNodeName, $fkNode);

			$referenceTableNode = self::getSingleElementByTagName($this->schemaNamespaceURI,
				$referenceNode, 'tableref', true);
			$foreignTable = null;

			if ($referenceTableNode->hasAttribute('id'))
			{
				$id = $referenceTableNode->getAttribute('id');
				if (!$this->identifiedElements->offsetExists($id))
				{
					throw new StructureException('Invalid table identifier ' . $id, $structure);
				}

				$foreignTable = $this->identifiedElements->offsetGet($id);
			}
			elseif ($referenceTableNode->hasAttribute('name'))
			{
				$name = $referenceTableNode->getAttribute('name');
				$foreignTable = $resolver->findTable($name);
			}

			if (!($foreignTable instanceof TableStructure))
			{
				throw new StructureException('Invalid foreign key reference table');
			}

			$foreignColumnNodes = $xpath->query($columnNodeName, $referenceNode);

			$fk = new ForeignKeyTableConstraint($foreignTable);
			if ($fkNode->hasAttribute('name'))
				$fk->constraintName = $fkNode->getAttribute('name');

			for ($i = 0; $i < $columnNodes->length; $i++)
			{
				$columnNode = $columnNodes->item($i);
				$foreignColumnNode = $foreignColumnNodes->item($i);

				$name = $columnNode->getAttribute('name');
				$foreignColumnName = $foreignColumnNode->getAttribute('name');

				if (!$structure->offsetExists($name))
				{
					throw new StructureException('Invalid foreign key column "' . $name . '"',
						$structure);
				}

				if (!$foreignTable->offsetExists($foreignColumnName))
				{
					throw new StructureException(
						'Invalid foreign key column "' . $foreignColumnName . '"', $foreignTable);
				}

				$fk->addColumn($name, $foreignColumnName);

				$events = [
					'onUpdate',
					'onDelete'
				];

				$actions = [
					'cascade' => K::FOREIGN_KEY_ACTION_CASCADE,
					'restrict' => K::FOREIGN_KEY_ACTION_RESTRICT,
					'default' => K::FOREIGN_KEY_ACTION_SET_DEFAULT,
					'null' => K::FOREIGN_KEY_ACTION_SET_NULL
				];

				foreach ($events as $event)
				{
					$eventNode = self::getSingleElementByTagName($this->schemaNamespaceURI, $fkNode,
						strtolower($event));
					if ($eventNode)
					{
						$action = $eventNode->getAttribute('action');
						if (ns\Container::keyExists($actions, $action))
						{
							$fk->$event = $actions[$action];
						}
					}
				}
			}

			$structure->addConstraint($fk);
		}
	}

	/**
	 *
	 * @var \ArrayObject
	 */
	private $foreignKeys;

	private $identifiedElements;

	/**
	 *
	 * @var \NoreSources\SemanticVersion
	 */
	private $schemaVersion;

	private $schemaNamespaceURI;
}

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