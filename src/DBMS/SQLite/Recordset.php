<?php

// NAmespace
namespace NoreSources\SQL\SQLite;

// Aliases
use NoreSources as ns;
use NoreSources\SQL as sql;
use NoreSources\SQL\Constants as K;

class Recordset extends sql\Recordset
{

	public function __construct(\SQLite3Result $result)
	{
		parent::__construct();
		$this->result = $result;
	}

	public function getColumnCount()
	{
		return $this->result->numColumns();
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see Iterator::next()
	 */
	public function next()
	{
		$mode = 0;
		if ($this->flags & self::FETCH_ASSOCIATIVE)
			$mode |= \SQLITE3_ASSOC;
		if ($this->flags | self::FETCH_INDEXED)
			$mode |= \SQLITE3_NULL;

		$a = $this->result->fetchArray($mode);

		if ($a === FALSE)
		{
			$this->setPosition(-1, self::POSITION_END);
		}
		else
		{
			$this->record->exchangeArray($a);
			$this->setPosition($this->rowIndex + 1, 0);
		}
	}

	public function rewind()
	{
		$r = $this->result->reset();
		$this->setPosition(-1, ($r ? self::POSITION_BEGIN : self::POSITION_END));
	}

	/**
	 *
	 * @var \SQLite3Result
	 */
	private $result;
}