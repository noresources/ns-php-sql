<?php

// Namespace
namespace NoreSources\SQL;

// Aliases
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Expression as X;
use NoreSources\Expression as xpr;
use NoreSources as ns;

/**
 * Record column description
 */
class ResultColumn implements ColumnPropertyMap
{

	use ColumnPropertyMapTrait;

	/**
	 *
	 * @var string
	 */
	public $name;

	/**
	 *
	 * @property-read integer $dataType
	 * @param string $member
	 * @throws \InvalidArgumentException
	 * @return boolean|number|NULL|string|string
	 */
	public function __get($member)
	{
		if ($member == 'dataType')
		{
			if ($this->hasColumnProperty(K::COLUMN_PROPERTY_DATA_TYPE))
				return $this->getColumnProperty(K::COLUMN_PROPERTY_DATA_TYPE);
			return k::DATATYPE_UNDEFINED;
		}

		throw new \InvalidArgumentException(
			$member . ' is not a member of ' . ns\TypeDescription::getName($this));
	}

	/**
	 *
	 * @property-write integer $dataType
	 * @param string $member
	 * @param mixed $value
	 * @throws \InvalidArgumentException
	 */
	public function __set($member, $value)
	{
		if ($member == 'dataType')
		{
			$this->setColumnProperty(k::COLUMN_PROPERTY_DATA_TYPE, $value);
			return;
		}

		throw new \InvalidArgumentException(
			$member . ' is not a member of ' . ns\TypeDescription::getName($this));
	}

	/**
	 *
	 * @param integer|TableColumnStructure $data
	 */
	public function __construct($data)
	{
		if ($data instanceof TableColumnStructure)
			$this->name = $data->getName();
		elseif (ns\TypeDescription::hasStringRepresentation($data))
			$this->name = ns\TypeConversion::toString($data);

		if ($data instanceof TableColumnStructure)
		{
			$this->initializeColumnProperties($data->getColumnProperties());
		}
		else
			$this->initializeColumnProperties();
	}
}

/**
 */
class ResultColumnMap implements \Countable, \IteratorAggregate
{

	public function __construct()
	{
		$this->columns = new \ArrayObject();
	}

	public function __get($key)
	{
		return $this->getColumn($key);
	}

	public function getIterator()
	{
		return $this->columns->getIterator();
	}

	public function count()
	{
		return $this->columns->count();
	}

	/**
	 *
	 * @param integer|string $key
	 * @throws \InvalidArgumentException
	 * @return ResultColumn
	 */
	public function getColumn($key)
	{
		if (!$this->columns->offsetExists($key))
		{
			foreach ($this->columns as $column)
			{
				if ($column->name == $key)
					return $column;
			}

			throw new \InvalidArgumentException(
				ns\TypeDescription::getName($key) . ' ' . $key . ' is not a valid result column key');
		}

		return $this->columns->offsetGet($key);
	}

	public function setColumn($index, $data)
	{
		if (!($data instanceof ResultColumn))
			$data = new ResultColumn($data);
		$this->columns->offsetSet($index, $data);
	}

	/**
	 *
	 * @var \ArrayObject
	 */
	private $columns;
}


