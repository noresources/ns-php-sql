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

require_once ('sql.php');
require_once (NS_PHP_PATH . '/core/strings.php');
require_once (NS_PHP_PATH . '/core/arrays.php');

class StructureVersion
{

	public function __construct($version)
	{
		if (is_string($version))
		{
			$this->m_versionArray = explode('.', $version);
		}
		else
		{
			$this->m_versionArray = array (
					0,
					0 
			);
		}
	}

	public function __get($member)
	{
		if ($member == 'major')
		{
			return array_key_exists(0, $this->m_versionArray) ? $this->m_versionArray [0] : 0;
		}
		elseif ($member == 'minor')
		{
			return array_key_exists(1, $this->m_versionArray) ? $this->m_versionArray [1] : 0;
		}
		elseif ($member == 'patch')
		{
			return array_key_exists(2, $this->m_versionArray) ? $this->m_versionArray [2] : 0;
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

	public function __toString()
	{
		return implode('.', $this->m_versionArray);
	}

	private $m_versionArray;
}

class StructureElement implements ArrayAccess, Iterator
{

	protected function __construct($a_name, $a_parent = null)
	{
		$this->m_name = $a_name;
		$this->m_parent = $a_parent;
		
		$this->m_children = array ();
		$this->m_iteratorCurrent = null;
	}
	
	// ArrayAccess
	public function offsetExists($a_key)
	{
		return array_key_exists($a_key, $this->m_children);
	}

	public function offsetSet($a_iKey, $a_value)
	{
		ns\Reporter::error($this, __METHOD__ . '(): Read only access', __FILE__, __LINE__);
	}

	public function offsetUnset($a_iKey)
	{
		ns\Reporter::error($this, __METHOD__ . '(): Read only access', __FILE__, __LINE__);
	}

	public function offsetGet($a_iKey)
	{
		$v = ns\array_keyvalue($this->m_children, $a_iKey, null);
		if (!$v)
		{
			$v = ns\array_keyvalue($this->m_children, strtolower($a_iKey), null);
		}
		
		return $v;
	}
	
	// Iterator
	public function current()
	{
		return $this->m_iteratorCurrent [1];
	}

	public function next()
	{
		if ($this->m_iteratorCurrent)
		{
			$this->m_iteratorCurrent = each($this->m_children);
		}
	}

	public function key()
	{
		return $this->m_iteratorCurrent [0];
	}

	public function valid()
	{
		return ($this->m_iteratorCurrent !== false);
	}

	public function rewind()
	{
		reset($this->m_children);
		$this->m_iteratorCurrent = each($this->m_children);
	}

	public function elementKey()
	{
		return $this->m_name;
	}

	public function getName()
	{
		return $this->m_name;
	}

	public function parent()
	{
		return $this->m_parent;
	}

	public function children()
	{
		return $this->m_children;
	}

	protected function addChild(StructureElement $a_child)
	{
		$parent = $this->parent();
		$key = $a_child->elementKey();
		$this->m_children [$key] = $a_child;
	}

	protected function clear()
	{
		$this->m_children = array ();
		$this->m_iteratorCurrent = false;
	}

	protected function root()
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
	public function getStructureVersion()
	{
		if ($this->parent())
		{
			return $this->parent()->getStructureVersion();
		}
	}

	private $m_iteratorCurrent;

	private $m_name;

	private $m_parent;

	private $m_children;

	private $m_version;
}

/**
 * Table field properties
 */
class TableFieldStructure extends StructureElement
{

	public function __construct(SQLTableStructure $a_tableStructure, $a_name)
	{
		parent::__construct($a_name, $a_tableStructure);
		$this->m_fieldProperties = array (
				kStructureAcceptNull => true,
				kStructureAutoincrement => false,
				kStructureDecimalCount => 0,
				kStructureDataSize => 0,
				kStructurePrimaryKey => false,
				kStructureIndexed => false,
				kStructureDatatype => null,
				kStructureEnumeration => null,
				kStructureValidatorClassname => null 
		);
	}

	public function getProperties()
	{
		return $this->m_fieldProperties;
	}

	public function getProperty($a_strName)
	{
		return $this->m_fieldProperties [$a_strName];
	}

	public function setProperty($a_strName, $a_value)
	{
		if (array_key_exists($a_strName, $this->m_fieldProperties))
		{
			$this->m_fieldProperties [$a_strName] = $a_value;
		}
	}

	private $m_fieldProperties;
}

/**
 * Table properties
 *
 * @todo table constraints (primary keys etc. & index)
 */
class SQLTableStructure extends StructureElement
{

	public function __construct(SQLDatabaseStructure $a_databaseStructure, $a_name)
	{
		parent::__construct($a_name, $a_databaseStructure);
	}

	public function getName()
	{
		return $this->root()->getTablePrefix() . parent::getName();
	}

	public final function addFieldStructure(TableFieldStructure $a_fieldStructure)
	{
		$this->addChild($a_fieldStructure);
	}
}

/**
 * Database structure definition
 *
 * @author renaud
 */
class SQLDatabaseStructure extends StructureElement
{

	public function __construct(SQLDatasourceStructure $a_datasourceStructure, $a_name)
	{
		parent::__construct($a_name, $a_datasourceStructure);
	}

	public final function addTableStructure(SQLTableStructure $a_table)
	{
		$this->addChild($a_table);
	}
}

/**
 * Data source structure definition
 *
 * @author renaud
 */
class SQLDatasourceStructure extends StructureElement
{
	const XMLNAMESPACE = 'http://xsd.nore.fr/sql';

	public function __construct($a_name = 'Datasource', $flags = 0)
	{
		parent::__construct($a_name);
		$this->m_flags = $flags;
		$this->m_version = new StructureVersion('0.0.0');
	}

	public function getStructureVersion()
	{
		return $this->m_version;
	}

	public function getTablePrefix()
	{
		return $this->m_tablePrefix;
	}

	public function setTablePrefix($prefix)
	{
		$this->m_tablePrefix = $prefix;
	}

	public static function xmlAffinityToDatatype($v)
	{
		if ($v == 'integer')
		{
			return DATATYPE_NUMBER;
		}
		else if ($v == 'boolean')
		{
			return DATATYPE_BOOLEAN;
		}
		else if ($v == 'decimal')
		{
			return DATATYPE_NUMBER;
		}
		else if ($v == 'datetime')
		{
			return DATATYPE_TIMESTAMP;
		}
		else if ($v == 'string')
		{
			return DATATYPE_STRING;
		}
		
		return DATATYPE_BINARY;
	}

	/**
	 *
	 * @param string $a_filename
	 *        	XML SQL structure to load
	 * @param mixed $postProcessElementCallback
	 *        	A delegate called for each node. The currently processed node is passed as the first argument
	 * @return boolean
	 */
	public final function loadStructureFromXml($a_filename, $postProcessElementCallback = null)
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
		
		$xpath = new \DOMXPath($doc);
		$xpath->registerNamespace('sql', self::XMLNAMESPACE);
		$dbnodes = $xpath->query('//sql:database');
		
		foreach ($dbnodes as $dbnode)
		{
			$dbs = new SQLDatabaseStructure($this, $dbnode->getAttribute('name'));
			
			$tnodes = $xpath->query('sql:table', $dbnode);
			foreach ($tnodes as $tnode)
			{
				$ts = new SQLTableStructure($dbs, $tnode->getAttribute('name'));
				
				$fnodes = $xpath->query('sql:field', $tnode);
				foreach ($fnodes as $fnode)
				{
					$fs = new TableFieldStructure($ts, $fnode->getAttribute('name'));
					$child = $fnode->getElementsByTagNameNS(self::XMLNAMESPACE, 'datatype');
					
					if ($child && $child->length)
					{
						if ($this->getStructureVersion()->major < 2 || $this->getStructureVersion()->minor == 0) // 1.x, 2.0
						{
							$subchild = $child->item(0)->getElementsByTagNameNS(self::XMLNAMESPACE, 'affinity');
							if ($subchild && $subchild->length)
							{
								$affinity = $subchild->item(0)->nodeValue;
								$fs->setProperty(kStructureDatatype, SQLDatasourceStructure::xmlAffinityToDatatype($affinity));
							}
							$subchild = $child->item(0)->getElementsByTagNameNS(self::XMLNAMESPACE, 'length');
							if ($subchild && $subchild->length)
							{
								$fs->setProperty(kStructureDataSize, floatval($subchild->item(0)->nodeValue));
							}
							
							$subchild = $child->item(0)->getElementsByTagNameNS(self::XMLNAMESPACE, 'decimal');
							if ($subchild && $subchild->length)
							{
								$fs->setProperty(kStructureDecimalCount, floatval($subchild->item(0)->nodeValue));
							}
						}
						elseif ($this->getStructureVersion()->major == 2)
						{
							$dataTypeNode = $child->item(0);
							$a = array (
									'binary' => DATATYPE_BINARY,
									'boolean' => DATATYPE_BOOLEAN,
									'numeric' => DATATYPE_NUMBER,
									'timestamp' => DATATYPE_TIMESTAMP,
									'string' => DATATYPE_STRING 
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
							
							if ($type == DATATYPE_NUMBER)
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
					}
					
					$child = $fnode->getElementsByTagNameNS(self::XMLNAMESPACE, 'notnull');
					if ($child && $child->length)
					{
						$fs->setProperty(kStructureAcceptNull, false);
					}
					
					$child = $fnode->getElementsByTagNameNS(self::XMLNAMESPACE, 'default');
					if ($child && $child->length)
					{
						$fs->setProperty(kStructureDefaultValue, $child->item(0)->nodeValue);
					}
					
					$ts->addChild($fs);
					if (is_callable($postProcessElementCallback))
					{
						call_user_func($postProcessElementCallback, $fs);
					}
				} // foreach field nodes
				
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

	public final function addDatabaseStructure(SQLDatabaseStructure $a_database)
	{
		$this->addChild($a_database);
	}

	protected $m_flags;

	private $m_version;
	private $m_tablePrefix;
}

/**
 * An object part of a SQL Datasource structure
 */
class SQLObject
{
	protected function __construct($a_structure = null, $a_structureClassType = null)
	{
		if ($a_structureClassType === null)
		{
			$a_structureClassType = __NAMESPACE__ . '\\StructureElement';
		}

		if (is_object($a_structure) && is_a($a_structure, $a_structureClassType))
		{
			$this->m_structure = $a_structure;
		}
		else
		{
			$this->m_structure = null;
		}
	}

	public function __get($member)
	{
		if ($member == 'structure')
		{
			return $this->getStructure();
		}
		
		throw new \InvalidArgumentException(get_class($this) . '::' . $member);
	}
	
	public final function getStructure()
	{
		return $this->m_structure;
	}

	protected $m_structure;
}

?>