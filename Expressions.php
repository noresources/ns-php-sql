<?php

/**
 * Copyright © 2012-2018 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * A set of ns\IExpression designed to be used in queries
 * 
 * @package SQL
 */
namespace NoreSources\SQL;

use NoreSources as ns;

/**
 * IS null expression
 */
class SQLIsNull extends ns\UnaryOperatorExpression
{

	public function __construct(Datasource $a_datasource, $a_bPositive = true)
	{
		$expValue = $a_datasource->createData(kDataTypeNull);
		if (!$a_bPositive)
		{
			$expValue = new ns\UnaryOperatorExpression('NOT ', $expValue);
			$expValue->protect(false);
		}
		
		parent::__construct('IS ', $expValue);
		$this->protect(false);
	}
}

/**
 * IN (value1, value2, .
 *
 *
 *
 *
 *
 *
 * .) expression
 */
class SQLIn extends ns\UnaryOperatorExpression
{

	public function __construct(Datasource $a_datasource, $a_value, $a_bPositive = true)
	{
		if ($a_value instanceof SelectQuery)
		{
			if ($a_value->columnCount != 1)
			{
				ns\Reporter::warning($this, __METHOD__ . '(): SelectQuery does not have exacty one column', __FILE__, __LINE__);
			}
			$a_value = new ns\SurroundingElementExpression($a_value);
		}
		elseif ($a_value instanceof SQLDataArray)
		{
			if (is_array($a_value))
			{
				$a_value = new SQLDataArray($a_datasource, $a_value);
			}
			else
			{
				ns\Reporter::fatalError($this, __METHOD__ . '(): Invalid parameter', __FILE__, __LINE__);
			}
		}
		
		if (!$a_bPositive)
		{
			$expValue = new ns\UnaryOperatorExpression('IN ', $a_value);
			parent::__construct('NOT ', $expValue);
		}
		else
		{
			parent::__construct('IN ', $a_value);
		}
	}
}

/**
 * A smart 'equal' depending on values given
 * - IS null, NOT IS null if value is SQLNull
 * - IN, NOT IN if value is an array or a select query
 * - = or <> operator otherwise
 */
class SQLSmartEquality extends ns\BinaryOperatorExpression
{

	public function __construct(ns\IExpression $a_column, $a_value, $a_bEqual = true)
	{
		$strOperator = '=';
		
		// force to construct a FormattedData object
		if (!(($a_value instanceof Data) || ($a_value instanceof SelectQuery) || ($a_value instanceof DataList) || ($a_value instanceof SQLFunction)))
		{
			if ($a_column instanceof TableColumn)
			{
				$t = $a_column->type();
				if (is_array($a_value))
				{
					$a_value = DataList::fromList($a_value, $a_column);
				}
				elseif (!is_null($t))
				{
					$data = $a_column->datasource->createData($t);
					$data->import($a_value);
					$a_value = $data;
				}
				else
				{
					$a_value = bestEffortImport($a_value, $a_column->datasource);
				}
			}
			else
			{
				ns\Reporter::addWarning($this, __METHOD__ . '(): Argument 1 is not a TableColumn and argument 2 is not a SQLValue. ' . 'The method will not be able to precisely determine data type.', __FILE__, __LINE__);
				$a_value = bestEffortImport($a_value);
			}
		}
		
		if (($a_value instanceof SelectQuery) || ($a_value instanceof DataList))
		{
			if ($a_bEqual)
			{
				parent::__construct('IN', $a_column, new ns\SurroundingElementExpression($a_value));
			}
			else
			{
				$in = new SQLIn($a_column->datasource, $a_value, true);
				$in->protect(false);
				parent::__construct('NOT', $a_column, $in);
			}
		}
		elseif ($a_value instanceof NullData)
		{
			parent::__construct('IS' . ($a_bEqual ? '' : ' NOT'), $a_column, $a_value);
		}
		else
		{
			if ($a_bEqual)
			{
				parent::__construct('=', $a_column, $a_value);
			}
			else
			{
				parent::__construct('<>', $a_column, $a_value);
			}
		}
		
		$this->protect(false);
	}
}

/**
 * A 'field BETWEEN value1 AND value2' expression
 */
class SQLBetween extends ns\BinaryOperatorExpression
{

	/**
	 *
	 * @param TableColumn $a_leftExpression
	 * @param mixed $a_min basic type Function or SQLValue
	 * @param mixed $a_max basic type Function or SQLValue
	 */
	public function __construct(ns\IExpression $a_leftExpression, $a_min, $a_max)
	{
		parent::__construct('BETWEEN', $a_leftExpression);
		$this->protect(false);
		
		if (!($a_min instanceof ns\IExpression))
		{
			if ($a_leftExpression instanceof TableColumn)
			{
				$a_min = $a_leftExpression->importData($a_min);
			}
			else
			{
				ns\Reporter::fatalError($this, __METHOD__ . ': Unable to create MIN expression', __FILE__, __LINE__);
			}
		}
		
		if (!($a_max instanceof ns\IExpression))
		{
			if ($a_leftExpression instanceof TableColumn)
			{
				$a_max = $a_leftExpression->importData($a_max);
			}
			else
			{
				ns\Reporter::fatalError($this, __METHOD__ . ': Unable to create MAX expression', __FILE__, __LINE__);
			}
		}
		
		$x = new ns\BinaryOperatorExpression('AND', $a_min, $a_max);
		$this->rightExpression($x);
	}
}

/**
 * BETWEEN, <= or >= depending on right expression settings
 */
class AutoInterval extends ns\BinaryOperatorExpression
{

	public function __construct(ns\IExpression $a_leftExpression, $a_min, $a_max)
	{
		parent::__construct(($a_min && $a_max) ? 'BETWEEN' : (($a_min) ? '>= ' : '<='), $a_leftExpression);
		$this->protect(false);
		
		if (!($a_min || $a_max))
		{
			ns\Reporter::fatalError($this, 'Missing min or max parameter', __FILE__, __LINE__);
		}
		
		$t = null;
		if ($a_leftExpression instanceof TableColumn)
		{
			$t = $a_leftExpression->type();
		}
		
		if ($a_min)
		{
			if (!($a_min instanceof ns\IExpression))
			{
				if ($t)
				{
					$a_min = createValue($t, $a_min, $a_leftExpression->datasource);
				}
				else
				{
					ns\Reporter::fatalError($this, 'Invalid min expression', __FILE__, __LINE__);
				}
			}
		}
		
		if ($a_max)
		{
			if ($t)
			{
				$a_max = createValue($t, $a_max, $a_leftExpression->datasource);
			}
			else
			{
				ns\Reporter::fatalError($this, 'Invalid max expression', __FILE__, __LINE__);
			}
		}
		
		if ($a_min && $a_max)
		{
			$this->rightExpression(new ns\BinaryOperatorExpression('AND', $a_min, $a_max));
		}
		elseif ($a_min)
		{
			$this->rightExpression($a_min);
		}
		else
		{
			$this->rightExpression($a_max);
		}
	}
}

/**
 * Timestamp formatting function
 */
class TimestampFormatFunction extends SQLFunction
{

	/**
	 *
	 * @param Datasource $datasource
	 * @param string $format strftime format string
	 * @param mixed $timestamp
	 */
	public function __construct(Datasource $datasource, $format, $timestamp)
	{
		parent::__construct('strftime');
		if ($datasource && is_string($format) && $timestamp)
			$this->setTimestamp($datasource, $format, $timestamp);
	}

	public function addParameter(ns\IExpression $a_paramter)
	{
		throw new \BadMethodCallException(__METHOD__ . '() is not allowed. Use setTimestampt() instead');
	}

	public function setTimestamp(Datasource $datasource, $format, $timestamp)
	{
		$this->clearParameters();
		
		if (!($timestamp instanceof ns\IExpression))
		{
			$d = $datasource->createData(kDataTypeTimestamp);
			$d->import($timestamp);
			$timestamp = $d;
		}
		
		if ($datasource instanceof PostgreSQLDatasource)
		{
			$f = new SQLFunction('CAST');
			$e = new ns\BinaryOperatorExpression('AS', $timestamp, new FormattedData($datasource->getDefaultTypeName(kDataTypeTimestamp)));
			$e->protect = false;
			$f->addParameter($e);
			
			$timestamp = $f;
		}
		
		if ($datasource instanceof SQLiteDatasource)
		{
			$this->operator = 'strftime';
			$f = $datasource->createData(kDataTypeString);
			$f->import($format);
			parent::addParameter($f);
			parent::addParameter($timestamp);
		}
		else if ($datasource instanceof MySQLDatasource)
		{
			$this->operator = 'date_format';
			$format = preg_replace(array (
					'/%M/', // minute: 00-59
					'/%W/' // week of year: 00-53
			), array (
					'%i', // minute: 00-59), $format);
					'/%U/' // week of year: 00-53
			), $format);
			$f = $datasource->createData(kDataTypeString);
			$f->import($format);
			parent::addParameter($timestamp);
			parent::addParameter($f);
		}
		else // TO_CHAR
		{
			$this->operator = 'to_char';
			
			$format = preg_replace(array (
					'/%d/', // day of month: 00
					'/%f/', // fractional seconds: SS.SSS
					'/%H/', // hour: 00-24
					'/%j/', // day of year: 001-366
					'/%J/', // Julian day number
					'/%m/', // month: 01-12
					'/%M/', // minute: 00-59
					'/%s/', // seconds since 1970-01-01
					'/%S/', // seconds: 00-59
					'/%w/', // day of week 0-6 with Sunday==0
					'/%W/', // week of year: 00-53
					'/%Y/', // year: 0000-9999
					'/%%/' // %
			), array (
					'DD', // day of month: 00
					'SS.MS', // fractional seconds: SS.SSS
					'HH24', // hour: 00-24
					'DDD', // day of year: 001-366
					'J', // Julian day number
					'MM', // month: 01-12
					'MI', // minute: 00-59
					'I0', // seconds since 1970-01-01
					'SS', // seconds: 00-59
					'ID', // day of week 0-6 with Sunday==0
					'IW', // MISMATCH week of year: 00-53
					'YYYY', // year: 0000-9999
					'%' // %
			), $format);
			
			parent::addParameter($timestamp);
			$f = $datasource->createData(kDataTypeString);
			$f->import($format);
			parent::addParameter($f);
		}
	}
}
