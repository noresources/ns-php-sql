<?php

/**
 * Copyright © 2012-2015 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */

/**
 *
 * @package SQL
 */
namespace NoreSources\SQL;
use NoreSources as ns;
use \ArrayAccess, Iterator;
require_once (NS_PHP_PATH . '/core/strings.php');
require_once (NS_PHP_PATH . '/core/arrays.php');

/**
 * SQL structure definition schema version
 */
class StructureVersion
{

	/**
	 *
	 * @param string $version        	
	 */
	public function __construct ($version)
	{
		if (is_string($version))
		{
			$this->m_versionArray = explode('.', $version);
		}
		else
		{
			$this->m_versionArray = array(
					0,
					0
			);
		}
	}

	/**
	 *
	 * @param unknown $member        	
	 * @return number
	 */
	public function __get ($member)
	{
		if ($member == 'major')
		{
			return array_key_exists(0, $this->m_versionArray) ? $this->m_versionArray[0] : 0;
		}
		elseif ($member == 'minor')
		{
			return \array_key_exists(1, $this->m_versionArray) ? $this->m_versionArray[1] : 0;
		}
		elseif ($member == 'patch')
		{
			return \array_key_exists(2, $this->m_versionArray) ? $this->m_versionArray[2] : 0;
		}
		elseif ($member == 'version' || $member == 'versionString')
		{
			return implode('.', $this->m_versionArray);
		}
		elseif ($member == 'versionNumber')
		{
			$v = 0;
			$m = 10000;
			foreach ($this->m_versionArray as $part)
			{
				$v += (intval($part) * $m);
				$m /= 100;
			}
			
			return $v;
		}
		
		throw new \InvalidArgumentException(get_class($this) . '::' . $member);
	}

	/**
	 *
	 * @return string
	 */
	public function __toString ()
	{
		return implode('.', $this->m_versionArray);
	}

	/**
	 *
	 * @var array
	 */
	private $m_versionArray;
}

class StructureElement implements ArrayAccess, Iterator
{

	/**
	 *
	 * @param string $a_name
	 *        	StructureElement
	 * @param StructureElement $a_parent        	
	 */
	protected function __construct ($a_name, $a_parent = null)
	{
		$this->m_name = $a_name;
		$this->m_parent = $a_parent;
		
		$this->m_children = array();
		$this->m_iteratorCurrent = null;
	}
	
	// ArrayAccess
	public function offsetExists ($a_key)
	{
		return array_key_exists($a_key, $this->m_children);
	}

	public function offsetSet ($a_iKey, $a_value)
	{
		ns\Reporter::error($this, __METHOD__ . '(): Read only access', __FILE__, __LINE__);
	}

	public function offsetUnset ($a_iKey)
	{
		ns\Reporter::error($this, __METHOD__ . '(): Read only access', __FILE__, __LINE__);
	}

	public function offsetGet ($a_iKey)
	{
		$v = ns\array_keyvalue($this->m_children, $a_iKey, null);
		if (!$v)
		{
			$v = ns\array_keyvalue($this->m_children, strtolower($a_iKey), null);
		}
		
		return $v;
	}
	
	// Iterator
	public function current ()
	{
		return $this->m_iteratorCurrent[1];
	}

	public function next ()
	{
		if ($this->m_iteratorCurrent)
		{
			$this->m_iteratorCurrent = each($this->m_children);
		}
	}

	public function key ()
	{
		return $this->m_iteratorCurrent[0];
	}

	public function valid ()
	{
		return ($this->m_iteratorCurrent !== false);
	}

	public function rewind ()
	{
		reset($this->m_children);
		$this->m_iteratorCurrent = each($this->m_children);
	}

	/**
	 *
	 * @return string
	 */
	public function elementKey ()
	{
		return $this->m_name;
	}

	/**
	 *
	 * @return string
	 */
	public function getName ()
	{
		return $this->m_name;
	}

	/**
	 *
	 * @return StructureElement
	 */
	public function parent ()
	{
		return $this->m_parent;
	}

	public function children ()
	{
		return $this->m_children;
	}

	protected function addChild (StructureElement $a_child)
	{
		$parent = $this->parent();
		$key = $a_child->elementKey();
		$this->m_children[$key] = $a_child;
	}

	protected function clear ()
	{
		$this->m_children = array();
		$this->m_iteratorCurrent = false;
	}

	/**
	 *
	 * @return StructureElement
	 */
	protected function root ()
	{
		$res = $this;
		while ($res->parent())
		{
			$res = $res->parent();
		}
		
		return $res;
	}

	/**
	 *
	 * @return StructureVersion
	 */
	public function getStructureVersion ()
	{
		if ($this->parent())
		{
			return $this->parent()->getStructureVersion();
		}
	}

	private $m_iteratorCurrent;

	/**
	 *
	 * @var string
	 */
	private $m_name;

	/**
	 *
	 * @var StructureElement
	 */
	private $m_parent;

	/**
	 *
	 * @var array
	 */
	private $m_children;

	/**
	 *
	 * @var StructureVersion
	 */
	private $m_version;
}

/**
 * Table field properties
 */
class TableFieldStructure extends StructureElement
{

	public function __construct (TableStructure $a_tableStructure, $a_name)
	{
		parent::__construct($a_name, $a_tableStructure);
		$this->m_fieldProperties = array(
				kStructureAcceptNull => true,
				kStructureAutoincrement => false,
				kStructureDecimalCount => 0,
				kStructureDataSize => 0,
				kStructurePrimaryKey => false,
				kStructureIndexed => false,
				kStructureDatatype => kDataTypeString,
				kStructureEnumeration => null,
				kStructureValidatorClassname => null
		);
	}

	/**
	 *
	 * @return array
	 */
	public function getProperties ()
	{
		return $this->m_fieldProperties;
	}

	public function getProperty ($a_strName)
	{
		return $this->m_fieldProperties[$a_strName];
	}

	public function setProperty ($a_strName, $a_value)
	{
		if (array_key_exists($a_strName, $this->m_fieldProperties))
		{
			$this->m_fieldProperties[$a_strName] = $a_value;
		}
	}

	private $m_fieldProperties;
}

/**
 * Table properties
 *
 * @todo table constraints (primary keys etc. & index)
 */
class TableStructure extends StructureElement
{

	public function __construct (DatabaseStructure $a_databaseStructure, $a_name)
	{
		parent::__construct($a_name, $a_databaseStructure);
	}

	public function getName ()
	{
		return $this->root()->getTablePrefix() . parent::getName();
	}

	public final function addFieldStructure (TableFieldStructure $a_fieldStructure)
	{
		$this->addChild($a_fieldStructure);
	}
}

/**
 * Database structure definition
 *
 * @author renaud
 */
class DatabaseStructure extends StructureElement
{

	public function __construct (DatasourceStructure $a_datasourceStructure, $a_name)
	{
		parent::__construct($a_name, $a_datasourceStructure);
	}

	public final function addTableStructure (TableStructure $a_table)
	{
		$this->addChild($a_table);
	}
}

/**
 * Data source structure definition
 *
 * @author renaud
 */
class DatasourceStructure extends StructureElement
{

	/**
	 *
	 * @var string ns-xml SQL schema namespace
	 */
	const XMLNAMESPACE = 'http://xsd.nore.fr/sql';

	/**
	 *
	 * @param string $a_name
	 *        	Datasource class name
	 * @param number $flags        	
	 */
	public function __construct ($a_name = 'Datasource', $flags = 0)
	{
		parent::__construct($a_name);
		$this->m_flags = $flags;
		$this->m_version = new StructureVersion('0.0.0');
	}

	/**
	 *
	 * @see \NoreSources\SQL\StructureElement::getStructureVersion()
	 * @return StructureVersion
	 */
	public function getStructureVersion ()
	{
		return $this->m_version;
	}

	/**
	 *
	 * @return string
	 */
	public function getTablePrefix ()
	{
		return $this->m_tablePrefix;
	}

	/**
	 *
	 * @param string $prefix        	
	 */
	public function setTablePrefix ($prefix)
	{
		$this->m_tablePrefix = $prefix;
	}

	/**
	 *
	 * @param string $v
	 *        	Type affinity name
	 * @return integer
	 */
	public static function xmlAffinityToDatatype ($v)
	{
		if ($v == 'integer')
		{
			return kDataTypeNumber;
		}
		else 
			if ($v == 'boolean')
			{
				return kDataTypeBoolean;
			}
			else 
				if ($v == 'decimal')
				{
					return kDataTypeNumber;
				}
				else 
					if ($v == 'datetime')
					{
						return kDataTypeTimestamp;
					}
					else 
						if ($v == 'string')
						{
							return kDataTypeString;
						}
		
		return kDataTypeBinary;
	}

	/**
	 *
	 * @param string $a_filename
	 *        	XML SQL structure to load
	 * @param mixed $postProcessElementCallback
	 *        	A delegate called for each node. The currently processed node
	 *        	is passed as the
	 *        	first argument
	 * @return boolean
	 */
	public final function loadStructureFromXml ($a_filename, $postProcessElementCallback = null)
	{
		$this->clear();
		
		if (!file_exists($a_filename))
		{
			return ns\Reporter::error($this, __METHOD__ . ': structure file not found', __FILE__, __LINE__);
		}
		
		$doc = new \DOMDocument();
		if (!$doc->load($a_filename))
		{
			return ns\Reporter::error($this, __METHOD__ . ': failed to load structure', __FILE__, __LINE__);
		}
		
		$root = $doc->documentElement;
		if (!($root && $root->namespaceURI == self::XMLNAMESPACE))
		{
			return ns\Reporter::error($this, __METHOD__ . ': invalid namespace', __FILE__, __LINE__);
		}
		
		$versionNS = $root->getAttributeNS(self::XMLNAMESPACE, 'version');
		$version = $root->getAttribute('version');
		
		if (!$version)
		{
			$version = $versionNS;
		}
		
		$this->m_version = new StructureVersion($version);
		
		if ($this->getStructureVersion()->major != 1)
		{
			return ns\Reporter::fatalError($this, __METHOD__ . ': Unsupported structure schema version');
		}
		
		$xpath = new \DOMXPath($doc);
		$xpath->registerNamespace('sql', self::XMLNAMESPACE);
		$dbnodes = $xpath->query('//sql:database');
		
		foreach ($dbnodes as $dbnode)
		{
			$dbs = new DatabaseStructure($this, $dbnode->getAttribute('name'));
			
			$tnodes = $xpath->query('sql:table', $dbnode);
			foreach ($tnodes as $tnode)
			{
				$ts = new TableStructure($dbs, $tnode->getAttribute('name'));
				
				$primaryKeyColumnNodes = $xpath->query('sql:primarykey/sql:column', $tnode); 
								
				$columnNodes = $xpath->query('sql:column|sql:field', $tnode);
				foreach ($columnNodes as $columnNode)
				{
					$fs = new TableFieldStructure($ts, $columnNode->getAttribute('name'));
					$child = $columnNode->getElementsByTagNameNS(self::XMLNAMESPACE, 'datatype');
					
					if ($child && $child->length)
					{
						if ($this->getStructureVersion()->major == 1)
						{
							$dataTypeNode = $child->item(0);
							$a = array(
									'binary' => kDataTypeBinary,
									'boolean' => kDataTypeBoolean,
									'numeric' => kDataTypeNumber,
									'timestamp' => kDataTypeTimestamp,
									'string' => kDataTypeString
							);
							$typeNode = null;
							$type = null;
							foreach ($a as $k => $v)
							{
								$typeNode = $dataTypeNode->getElementsByTagNameNS(self::XMLNAMESPACE, $k);
								if ($typeNode && $typeNode->length)
								{
									$typeNode = $typeNode->item(0);
									$fs->setProperty(kStructureDatatype, $v);
									$type = $v;
									break;
								}
							}
							
							if ($type == kDataTypeNumber)
							{
								if ($typeNode->hasAttribute('autoincrement'))
								{
									$fs->setProperty(kStructureAutoincrement, true);
								}
								if ($typeNode->hasAttribute('length'))
								{
									$fs->setProperty(kStructureDataSize, intval($typeNode->getAttribute('length')));
								}
								if ($typeNode->hasAttribute('decimals'))
								{
									$fs->setProperty(kStructureDecimalCount, intval($typeNode->getAttribute('decimals')));
								}
							}
						}
					} // datatypes
					
					$child = $columnNode->getElementsByTagNameNS(self::XMLNAMESPACE, 'notnull');
					if ($child && $child->length)
					{
						$fs->setProperty(kStructureAcceptNull, false);
					}
					
					$child = $columnNode->getElementsByTagNameNS(self::XMLNAMESPACE, 'default');
					if ($child && $child->length)
					{
						$fs->setProperty(kStructureDefaultValue, $child->item(0)->nodeValue);
					}

					// Check if column is part of the primary key
					foreach ($primaryKeyColumnNodes as $primaryKeyColumnNode)
					{
						if ($primaryKeyColumnNode->getAttribute ("name") == $fs->getName())
						{
							$fs->setProperty(kStructurePrimaryKey, true);
						}
					}
					
					$ts->addChild($fs);
					if (is_callable($postProcessElementCallback))
					{
						call_user_func($postProcessElementCallback, $fs);
					}
				} // foreach column nodes
				
				$dbs->addChild($ts);
				if (is_callable($postProcessElementCallback))
				{
					call_user_func($postProcessElementCallback, $ts);
				}
			} // foreach table nodes
			
			$this->addChild($dbs);
			if (is_callable($postProcessElementCallback))
			{
				call_user_func($postProcessElementCallback, $dbs);
			}
		} // foreach database nodes
		
		if (is_callable($postProcessElementCallback))
		{
			call_user_func($postProcessElementCallback, $this);
		}
		
		return true;
	}

	/**
	 *
	 * @param DatabaseStructure $a_database        	
	 */
	public final function addDatabaseStructure (DatabaseStructure $a_database)
	{
		$this->addChild($a_database);
	}

	protected $m_flags;

	/**
	 *
	 * @var StructureVersion
	 */
	private $m_version;

	/**
	 *
	 * @var string
	 */
	private $m_tablePrefix;
}

/**
 * An object part of a SQL Datasource structure
 */
class SQLObject
{

	/**
	 *
	 * @param StructureElement $a_structure        	
	 * @param string $a_structureClassType
	 *        	The expected StructureElement class type
	 */
	protected function __construct (StructureElement $a_structure = null, $a_structureClassType = null)
	{
		if ($a_structureClassType === null)
		{
			$a_structureClassType = __NAMESPACE__ . '\\StructureElement';
		}
		
		if (is_object($a_structure))
		{
			if (is_a($a_structure, $a_structureClassType))
			{
				$this->m_structure = $a_structure;
			}
			else
			{
				\Reporter::fatalError($this, 
						'Invalid structure object ' . get_class($a_structure) . ', ' . $a_structureClassType . ' expected', 
						__FILE__, __LINE__);
			}
		}
		else
		{
			$this->m_structure = null;
		}
	}

	public function __get ($member)
	{
		if ($member == 'structure')
		{
			return $this->getStructure();
		}
		
		throw new \InvalidArgumentException(get_class($this) . '::' . $member);
	}

	/**
	 *
	 * @return StructureElement
	 */
	public final function getStructure ()
	{
		return $this->m_structure;
	}

	/**
	 *
	 * @var StructureElement
	 */
	protected $m_structure;
}
