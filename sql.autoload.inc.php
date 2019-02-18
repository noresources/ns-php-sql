<?php
function autoload_NWM2OTk3NGIyMjdkOA($className)
{
	if ($className == 'NoreSources\SQL\Statement')
	{
		require_once(__DIR__ . '/StatementBuilder.php');
	}
 	elseif ($className == 'NoreSources\SQL\QueryDescription')
	{
		require_once(__DIR__ . '/StatementBuilder.php');
	}
 	elseif ($className == 'NoreSources\SQL\QueryTableReference')
	{
		require_once(__DIR__ . '/StatementBuilder.php');
	}
 	elseif ($className == 'NoreSources\SQL\SelectQueryDescription')
	{
		require_once(__DIR__ . '/StatementBuilder.php');
	}
 	elseif ($className == 'NoreSources\SQL\ColumnReference')
	{
		require_once(__DIR__ . '/StatementBuilder.php');
	}
 	elseif ($className == 'NoreSources\SQL\StatementBuilder')
	{
		require_once(__DIR__ . '/StatementBuilder.php');
	}
 	elseif ($className == 'NoreSources\SQL\GenericStatementBuilder')
	{
		require_once(__DIR__ . '/StatementBuilder.php');
	}
 	elseif ($className == 'NoreSources\SQL\StructureElement')
	{
		require_once(__DIR__ . '/Structures.php');
	}
 	elseif ($className == 'NoreSources\SQL\TableColumnStructure')
	{
		require_once(__DIR__ . '/Structures.php');
	}
 	elseif ($className == 'NoreSources\SQL\TableStructure')
	{
		require_once(__DIR__ . '/Structures.php');
	}
 	elseif ($className == 'NoreSources\SQL\TableSetStructure')
	{
		require_once(__DIR__ . '/Structures.php');
	}
 	elseif ($className == 'NoreSources\SQL\DatasourceStructure')
	{
		require_once(__DIR__ . '/Structures.php');
	}
 	elseif ($className == 'NoreSources\SQL\PDOBackend')
	{
		require_once(__DIR__ . '/pdo/PDO.php');
	}
 }
spl_autoload_register('autoload_NWM2OTk3NGIyMjdkOA');
