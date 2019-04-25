<?php

/**
 * Copyright © 2012-2018 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */

/**
 *
 * @package SQL
 */
namespace NoreSources\SQL;

use NoreSources as ns;

/**
 * Provide a validation on imported data
 */
interface ITableColumnValueValidator
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
class ListedElementTableColumnValueValidator implements ITableColumnValueValidator
{

	/**
	 *
	 * @param unknown $a_aElements
	 */
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
		$this->m_aValidValues[] = $a_value;
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\ITableColumnValueValidator::validate()
	 * @return boolean
	 */
	public function validate($a_value)
	{
		return in_array($a_value, $this->m_aValidValues, false);
	}

	protected $m_aValidValues;
}

/**
 * Validator for set-like fields
 */
class MultipleListedElementTableColumnValueValidator extends ListedElementTableColumnValueValidator
{

	public function __construct($a_aElements = null)
	{
		parent::__construct($a_aElements);
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\ListedElementTableColumnValueValidator::validate()
	 * @return boolean
	 */
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

interface ITableColumnValueValidatorProvider
{

	/**
	 * Get the ITableColumnValueValidator object (if present) of the field
	 *
	 * @return ITableColumnValueValidator
	 */
	function getColumnValueValidator();

	function setColumnValueValidator(ITableColumnValueValidator $a_validator = null);
}

/**
 * A Table column represent one column of a table
 */
abstract class ITableColumn extends SQLObject implements IExpression, IAliasable
{

	/**
	 *
	 * @param TableColumnStructure $a_structure
	 */
	public function __construct(TableColumnStructure $a_structure = null)
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

	/**
	 *
	 * @return Table
	 */
	abstract function getTable();

	/**
	 * Get column name
	 * @return string
	 */
	abstract function getName();
}

class ConstantColumn implements IExpression, IAliasable
{

	/**
	 *
	 * @param Datasource $datasource
	 * @param string $columnName
	 * @param mixed $value
	 */
	public function __construct(Datasource $datasource, $columnName, $value)
	{
		$this->datasource = $datasource;
		$this->value = $value;
		if (!($value instanceof ns\IExpression))
		{
			$this->value = $datasource->createData(Data::dataTypeFromValue($value));
			$this->value->import($value);
		}
		$this->alias = new Alias($datasource, $columnName);
	}

	public function getDatasource()
	{
		return $this->datasource;
	}

	public function expressionString($a_options = null)
	{
		if (($a_options & kExpressionElementDeclaration) == kExpressionElementDeclaration)
		{
			return $this->value->expressionString() . ' AS ' . $this->alias->expressionString();
		}
		elseif ($a_options & kExpressionElementName)
		{
			return $this->value->expressionString();
		}
		
		return $this->alias->expressionString();
	}

	public function hasAlias()
	{
		return true;
	}

	public function alias(Alias $alias = null)
	{
		if ($alias instanceof Alias)
		{
			$this->alias = $alias;
		}
		
		return $this->alias;
	}

	/**
	 *
	 * @var Datasource
	 */
	private $datasource;

	/**
	 *
	 * @var Data
	 */
	private $value;

	/**
	 *
	 * @var Alias
	 */
	private $alias;
}

class SelectQueryColumn extends ConstantColumn
{
	public function __construct(SelectQuery $query, $columnName)
	{
		parent::__construct($query->datasource, $columnName, new ns\SurroundingElementExpression($query));
	}
}

/**
 * The Star field is a special SQL syntax representing all
 * columns of a table
 */
class StarColumn extends ITableColumn
{

	/**
	 * CoOnstructor
	 *
	 * @param Table $a_table Table reference
	 */
	public function __construct(Table $a_table)
	{
		parent::__construct(null);
		$this->m_table = $a_table;
	}

	// ns\IExpression implementation
	/**
	 *
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
	
	// IAliased
	/**
	 *
	 * @return boolean
	 */
	public function hasAlias()
	{
		return false;
	}

	/**
	 *
	 * @return Alias
	 */
	public function alias(Alias $alias = null)
	{
		return null;
	}

	// ITableColumn implementation
	public function getTable()
	{
		return $this->m_table;
	}

	/**
	 *
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
 * A table column
 */
class TableColumn extends ITableColumn implements IAliasedClone, ITableColumnValueValidatorProvider, IAliasable
{

	/**
	 * Constructor
	 *
	 * @param Table $a_table Table reference
	 * @param string $a_strName field name
	 * @param string $a_strAlias Alias (optional)
	 * @param array $a_structure TableColumnStructure
	 */
	public function __construct(Table $a_table, $a_strName, $a_strAlias = null, TableColumnStructure $a_structure = null)
	{
		parent::__construct($a_structure);
		$this->m_table = $a_table;
		$this->m_fieldName = $a_strName;
		if (is_string($a_strAlias))
		{
			$this->m_alias = new Alias($this->getDatasource(), $a_strAlias);
		}
		$this->m_valueValidator = null;
		
		if ($this->structure)
		{
			if (($data = $this->structure->getProperty(kStructureValidatorClassname)))
			{
				// ns\Reporter::debug($this, 'Adding '.$data);
				$this->setColumnValueValidator(new $data($this->structure));
			}
			else if (($data = $this->structure->getProperty(kStructureEnumeration)))
			{
				if ($this->structure->getProperty(kStructureAcceptMultipleValues))
				{
					// ns\Reporter::debug($this, 'Adding MultipleListedElementTableColumnValueValidator');
					$v = new MultipleListedElementTableColumnValueValidator($data);
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
					// ns\Reporter::debug($this, 'Adding ListedElementTableColumnValueValidator');
					$v = new ListedElementTableColumnValueValidator($data);
					if ($this->structure)
					{
						if ($this->structure->getProperty(kStructureAcceptNull))
						{
							$v->addElement(null);
						}
					}
				}
				
				$this->setColumnValueValidator($v);
			}
		}
	}

	/**
	 *
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
			return $this->table->expressionString(kExpressionElementAlias) . '.' . $connection->encloseElement($this->getName()) . ($this->hasAlias() ? ' AS ' . $connection->encloseElement($this->alias()->getAliasName()) : '');
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
	 * @return TableColumn
	 */
	public function cloneWithOtherAlias($newAlias)
	{
		$cn = get_class($this);
		$result = new $cn($this->table, $this->m_fieldName, $newAlias, $this->structure);
		$result->m_structure = $this->m_structure;
		return $result;
	}

	// IAliasedClone implementation
	
	// IAliased
	/**
	 *
	 * @see \NoreSources\SQL\IAliasable::hasAlias()
	 * @return boolean
	 */
	public function hasAlias()
	{
		return is_a($this->m_alias, __NAMESPACE__ . '\\Alias');
	}

	/**
	 *
	 * @see \NoreSources\SQL\IAliasable::alias()
	 * @return Alias
	 */
	function alias(Alias $alias = null)
	{
		if ($alias)
		{
			$this->m_alias = $alias;
		}
		
		return $this->m_alias;
	}

	// ITableColumn implementation
	
	/**
	 * (non-PHPdoc)
	 *
	 * @return Table
	 */
	public function getTable()
	{
		return $this->m_table;
	}

	/**
	 *
	 * @see include/ns/php/lib/sources/sql/ITableColumn#getName()
	 */
	public function getName()
	{
		return $this->m_fieldName;
	}

	// end of ITableColumn implementation
	
	// ITableColumnValueValidatorProvider implementation
	public function getColumnValueValidator()
	{
		return $this->m_valueValidator;
	}

	public function setColumnValueValidator(ITableColumnValueValidator $a_validator = null)
	{
		$this->m_valueValidator = $a_validator;
	}

	// end of ITableColumnValueValidatorProvider implementation
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
	 *
	 * @return boolean
	 */
	public function indexed()
	{
		return ($this->structure && $this->structure->getProperty(kStructureIndexed));
	}

	/**
	 *
	 * @return boolean
	 */
	public function autoIncrement()
	{
		return ($this->structure && $this->structure->getProperty(kStructureAutoincrement));
	}

	/**
	 *
	 * @return integer
	 */
	public function size()
	{
		return $this->structure ? $this->structure->getProperty(kStructureDataSize) : null;
	}

	/**
	 *
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
	 * Create a new Data base on field type and import the given value
	 * @param mixed $a_value Value to import
	 * @return \NoreSources\SQL\Data
	 */
	public function importData($a_value)
	{
		if ($this->m_valueValidator && !$this->m_valueValidator->validate($a_value))
		{
			return ns\Reporter::error($this, __METHOD__ . '(): Value validation failed', __FILE__, __LINE__);
		}
		
		$data = null;
		$structure = $this->getStructure();
		$sqlType = $this->type();
		if (is_null($a_value))
		{
			$s = $this->getStructure();
			if ($s && ($s->getProperty(kStructureAcceptNull)))
			{
				$sqlType = kDataTypeNull;
			}
		}
		else if ($sqlType === false)
		{
			$sqlType = dataTypeFromValue($a_value);
		}
		
		$data = $this->getDatasource()->createData($sqlType);
		if ($structure)
			$data->configure($structure);
		
		$data->import($a_value);
		
		return $data;
	}

	/**
	 * Create a field=value expression
	 *
	 * @param $a_value
	 * @param $a_bEquality
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
	 *
	 * @var Alias
	 */
	protected $m_alias;

	/**
	 *
	 * @todo Name is given by structure
	 * @var string
	 */
	protected $m_fieldName;

	/**
	 *
	 * @var ITableColumnValueValidator
	 */
	protected $m_valueValidator;
}
