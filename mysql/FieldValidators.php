<?php

/**
 * Copyright © 2012-2017 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */

/**
 *
 * @package SQL
 */
namespace NoreSources\SQL;
use NoreSources as ns;

class MySQLEnumColumnValueValidator extends ListedElementTableColumnValueValidator
{

	public function __construct($a_properties)
	{
		parent::__construct($a_properties [kStructureEnumeration]);
		$this->m_bAcceptNull = ns\array_keyvalue($a_properties, kStructureAcceptNull, false);
	}

	public function validate($a_value)
	{
		if (is_null($a_value) && $this->m_bAcceptNull)
		{
			return true;
		}

		if (parent::validate($a_value))
		{
			return true;
		}

		if (is_int($a_value) && $a_value >= 0)
		{
			if ($a_value == 0 && $this->m_bAcceptNull)
			{
				return true;
			}

			if ($a_value <= count($this->m_aValidValues))
			{
				return true;
			}
		}

		return false;

	}

	protected $m_bAcceptNull;

}

/**
 * A set is a list of enum stored as a 64bit flags
 *
 */
class MySQLSetColumnValueValidator extends MultipleListedElementTableColumnValueValidator
{
	public function __construct($a_properties)
	{
		parent::__construct($a_properties[kStructureEnumeration]);
		$this->m_bAcceptNull = ns\array_keyvalue($a_properties, kStructureAcceptNull, false);
	}

	protected function isValidFlag($a_value)
	{
		$c = count($this->m_aValidValues);
		$v = intval($a_value);
		return (($v >> $c) <= 0);
	}

	public function validate($a_value)
	{
		if (is_null($a_value)
			&& $this->m_bAcceptNull)
		{
			return true;
		}

		/**@todo 64-bits flags & int values in array */

		if (is_numeric($a_value))
		{
			return ($this->isValidFlag($a_value))
			? true
			: ns\Reporter::error($this, __METHOD__."(): Invalid flag ".intval($a_value), __FILE__, __LINE__);
		}

		if (is_string($a_value))
		{
			if (preg_match("/.+,.+/i", $a_value))
			{
				$a_value = explode(",", $a_value);
			}
			elseif (!in_array($a_value, $this->m_aValidValues))
			{
				return ns\Reporter::error($this, __METHOD__."(): String validation failed", __FILE__, __LINE__);
			}
			return true;
		}

		// Accept both indicies or string
		if (is_array($a_value))
		{
			foreach ($a_value as $v)
			{
				if (is_numeric($v))
				{
					if (!$this->isValidFlag($v))
					{
						return ns\Reporter::error($this, __METHOD__."(): Invalid flag in array (".intval($v).")", __FILE__, __LINE__);
					}
				}
				elseif (!in_array($v, $this->m_aValidValues))
				{
					return ns\Reporter::error($this, __METHOD__."(): Invalid value ", __FILE__, __LINE__);
				}
			}

			return true;
		}

		return false;

	}

	protected $m_bAcceptNull;

}
