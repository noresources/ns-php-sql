<?php 

// NAmespace
namespace NoreSources\SQL;

// Aliases
use NoreSources as ns;
use NoreSources\SQL\Constants as K;

interface Recordset extends \Iterator
{
	function getColumnCount();
}
