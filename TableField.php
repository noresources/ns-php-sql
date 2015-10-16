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
use \InvalidArgumentException;

require_once (NS_PHP_PATH . '/core/arrays.php');

/**
 * Provide a validation on imported data
 */
interface ITableFieldValueValidator
{

	/**
	 *
	 * @param mixed $a_value        	
	 */
	function validate($a_value);
}

/**
 * Validator for enum-like fields
 */
class ListedElementTableFieldValueValidator implements ITableFieldValueValidator
{

	public function __construct($a_aElements = null)
	{
		$this->m_aValidValues = $a_aElements;
		if (!is_array($this->m_aValidValues))
		{
			$this->m_aValidValues = array ();
		}
	}

	public function addElement($a_value)
	{
		$this->m_aValidValues [] = $a_value;
	}

	public function validate($a_value)
	{
		return in_array($a_value, $this->m_aValidValues, false);
	}

	protected $m_aValidValues;
}

/**
 * Validator for set-like fields
 */
class MultipleListedElementTableFieldValueValidator extends ListedElementTableFieldValueValidator
{

	public function __construct($a_aElements = null)
	{
		parent::__construct($a_aElements);
	}

	public function validate($a_value)
	{
		if (is_array($a_value))
		{
			foreach ($a_value as $e)
			{
				if (!parent::validate($a_value))
				{
					return false;
				}
			}
			
			return true;
		}
		
		return in_array($a_value, $this->m_aValidValues, false);
	}
}

interface ITableFieldValueValidatorProvider
{

	/**
	 * Get the ITableFieldValueValidator object (if present) of the field
	 *
	 * @return ITableFieldValueValidator
	 */
	function getFieldValueValidator();

	function setFieldValueValidator(ITableFieldValueValidator $a_validator = null);
}

/**
 * A database field represent one column of a database table
 */
abstract class ITableField extends SQLObject implements IExpression, IAliasable
{

	/**
	 * @param TableFieldStructure $a_structure
	 */
	public function __construct(TableFieldStructure $a_structure = null)
	{
		parent::__construct($a_structure);
	}

	public function __get($member)
	{
		if ($member == 'datasource')
		{
			return $this->getTable()->getDatasource();
		}
		if ($member == 'table')
		{
			return $this->getTable();
		}
		else if ($member == 'name')
		{
			return $this->getName();
		}
		
		return parent::__get($member);
	}

	abstract function getTable();

	abstract function getName();
}

/**
 * The Star field is a special SQL syntax representing all
 * columns of a database table
 */
class StarColumn extends ITableField
{

	/**
	 * CoOnstructor
	 *
	 * @param Table $a_table
	 *        	Table reference
	 */
	public function __construct(Table $a_table)
	{
		parent::__construct(null);
		$this->m_table = $a_table;
	}
	
	// ns\IExpression implementation
	/**
	 * @return string
	 */
	public function expressionString($a_options = null)
	{
		/**
		 * @note appending table name does not work in MySQL
		 */
		return $this->table->expressionString(kExpressionElementAlias) . '.*';
	}

	/**
	 *
	 * @return Datasource
	 */
	public function getDatasource()
	{
		if ($this->m_table)
		{
			return $this->m_table->getDatasource();
		}
	}
	
	// end of IExpression implementation
	

	// ISQLAliased
	/**
	 * @return boolean
	 */
	public function hasAlias()
	{
		return false;
	}

	/**
	 * @return SQLAlias
	 */
	public function alias(SQLAlias $alias = null)
	{
		return null;
	}
	
	// ITableField implementation
	public function getTable()
	{
		return $this->m_table;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return '*';
	}

	/**
	 * Table reference
	 *
	 * @var Table
	 */
	protected $m_table;
}

/**
 * A database table field
 */
class TableField extends ITableField implements IAliasedClone, ITableFieldValueValidatorProvider, IAliasable
{

	/**
	 * Constructor
	 *
	 * @param Table $a_table
	 *        	Table reference
	 * @param string $a_strName
	 *        	field name
	 * @param string $a_strAlias
	 *        	Alias (optional)
	 * @param array $a_structure
	 *        	TableFieldStructure
	 */
	public function __construct(Table $a_table, $a_strName, $a_strAlias = null, TableFieldStructure $a_structure = null)
	{
		parent::__construct($a_structure);
		$this->m_table = $a_table;
		$this->m_fieldName = $a_strName;
		if (is_string($a_strAlias))
		{
			$this->m_alias = new SQLAlias($this->getDatasource(), $a_strAlias);
		}
		$this->m_valueValidator = null;
		
		if ($this->structure)
		{
			if (($data = $this->structure->getProperty(kStructureValidatorClassname)))
			{
				// ns\Reporter::debug($this, 'Adding '.$data);
				$this->setFieldValueValidator(new $data($this->structure));
			}
			else if (($data = $this->structure->getProperty(kStructureEnumeration)))
			{
				if ($this->structure->getProperty(kStructureAcceptMultipleValues))
				{
					// ns\Reporter::debug($this, 'Adding MultipleListedElementTableFieldValueValidator');
					$v = new MultipleListedElementTableFieldValueValidator($data);
					if ($this->structure)
					{
						if ($this->structure->getProperty(kStructureAcceptNull))
						{
							$v->addElement(null);
						}
					}
				}
				else
				{
					// ns\Reporter::debug($this, 'Adding ListedElementTableFieldValueValidator');
					$v = new ListedElementTableFieldValueValidator($data);
					if ($this->structure)
					{
						if ($this->structure->getProperty(kStructureAcceptNull))
						{
							$v->addElement(null);
						}
					}
				}
				
				$this->setFieldValueValidator($v);
			}
		}
	}
	
	/**
	 * @param string $member
	 */
	public function __get($member)
	{
		if ($member == 'type')
		{
			return $this->type();
		}

		return parent::__get($member);
	}
	
	// ns\IExpression implementation
	
	/**
	 *
	 * @return string
	 */
	public function expressionString($a_options = null)
	{
		$connection = $this->getDatasource();
		
		if (($a_options & kExpressionElementDeclaration) == kExpressionElementDeclaration)
		{
			return $this->table->expressionString(kExpressionElementAlias) . '.' . $connection->encloseElement($this->getName()) 
			. ($this->hasAlias() ? ' AS ' . $connection->encloseElement($this->alias()->getAliasName()) : '');
		}
		elseif ($a_options & kExpressionElementName)
		{
			return $this->table->expressionString(kExpressionElementAlias) . '.' . $connection->encloseElement($this->getName());
		}
		elseif ($this->hasAlias())
		{
			return $this->m_alias->expressionString();
		}
		
		return $this->table->expressionString(kExpressionElementAlias) . '.' . $connection->encloseElement($this->getName());
	}
	
	// IExpression implementation
	
	/**
	 *
	 * @return Datasource
	 */
	public function getDatasource()
	{
		$ds = null;
		if ($this->m_table)
		{
			$ds = $this->m_table->getDatasource();
		}
		
		return $ds;
	}
	
	// IAliasedClone implementation
	
	/**
	 *
	 * @return TableField
	 */
	public function cloneWithOtherAlias($newAlias)
	{
		$cn = get_class($this);
		$result = new $cn($this->table, $this->m_fieldName, $newAlias, $this->structure);
		return $result;
	}
	
	// IAliasedClone implementation
	
	// ISQLAliased
	/**
	 * @see \NoreSources\SQL\IAliasable::hasAlias()
	 * @return boolean
	 */
	public function hasAlias()
	{
		return is_a($this->m_alias, __NAMESPACE__ . '\\SQLAlias');
	}

	/**
	 * @see \NoreSources\SQL\IAliasable::alias()
	 * @return SQLAlias
	 */
	function alias(SQLAlias $alias = null)
	{
		if ($alias)
		{
			$this->m_alias = $alias;
		}
		
		return $this->m_alias;
	}
	
	// ITableField implementation
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see include/ns/php/lib/sources/sql/ITableField#table
	 */
	public function getTable()
	{
		return $this->m_table;
	}

	/**
	 *
	 * @see include/ns/php/lib/sources/sql/ITableField#getName()
	 */
	public function getName()
	{
		return $this->m_fieldName;
	}
	
	// end of ITableField implementation
	
	// ITableFieldValueValidatorProvider implementation
	
	/**
	 *
	 * @see include/ns/php/lib/sources/sql/ITableFieldValueValidatorProvider#getFieldValueValidator()
	 */
	public function getFieldValueValidator()
	{
		return $this->m_valueValidator;
	}

	/**
	 *
	 * @see include/ns/php/lib/sources/sql/ITableFieldValueValidatorProvider#setFieldValueValidator($a_validator)
	 */
	public function setFieldValueValidator(ITableFieldValueValidator $a_validator = null)
	{
		$this->m_valueValidator = $a_validator;
	}
	
	// end of ITableFieldValueValidatorProvider implementation
	public function __toString()
	{
		return $this->expressionString();
	}

	/**
	 *
	 * @return bool
	 */
	public function primary()
	{
		return ($this->structure && $this->structure->getProperty(kStructurePrimaryKey));
	}

	/**
	 * @return boolean
	 */
	public function indexed()
	{
		return ($this->structure && $this->structure->getProperty(kStructureIndexed));
	}

	/**
	 * @return boolean
	 */
	public function autoIncrement()
	{
		return ($this->structure && $this->structure->getProperty(kStructureAutoincrement));
	}

	/**
	 * @return integer
	 */
	public function size()
	{
		return $this->structure ? $this->structure->getProperty(kStructureDataSize) : null;
	}

	/**
	 * @return integer
	 */
	public function decimalSize()
	{
		return $this->structure ? $this->structure->getProperty(kStructureDecimalCount) : null;
	}

	/**
	 *
	 * @return int
	 */
	public function type()
	{
		if (!$this->structure)
		{
			return ns\Reporter::error($this, __METHOD__ . '(): No type defined for field ' . $this->expressionString(kExpressionElementDeclaration), __FILE__, __LINE__);
		}
		
		return $this->structure->getProperty(kStructureDatatype);
	}

	/**
	 * Create a new SQLData base on field type and import the given value
	 * @param mixed $a_value Value to import
	 * @return SQLData
	 */
	public function importData($a_value)
	{
		if ($this->m_valueValidator && !$this->m_valueValidator->validate($a_value))
		{
			return ns\Reporter::error($this, __METHOD__ . '(): Value validation failed', __FILE__, __LINE__);
		}
		
		$sqlType = $this->type();
		if ($sqlType === false)
		{
			$sqlType = guessDataType($a_value);
		}
		
		$v = $this->getDatasource()->createData($sqlType);
		$v->import($a_value);
		
		return $v;
	}

	/**
	 * Create a field=value expression
	 *
	 * @param
	 *        	$a_value
	 * @param
	 *        	$a_bEquality
	 * @return ns\IExpression
	 */
	public function equalityExpression($a_value, $a_bEquality = true)
	{
		$exp = new SQLSmartEquality($this, $a_value, $a_bEquality);
		return $exp;
	}

	/**
	 *
	 * @var Table
	 */
	protected $m_table;

	/**
	 * @var SQLAlias
	 */
	protected $m_alias;

	/**
	 * @todo Name is given by structure
	 * @var string
	 */
	protected $m_fieldName;

	/**
	 *
	 * @var ITableFieldValueValidator
	 */
	protected $m_valueValidator;
}

?>
