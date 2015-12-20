<?php
function autoload_NTY2ZjFmNzJjYmQ4NQ($className)
{
	if ($className == 'NoreSources\SQL\Database')
	{
		require_once(__DIR__ . '/Database.php');
	}
 	elseif ($className == 'NoreSources\SQL\Data')
	{
		require_once(__DIR__ . '/Data.php');
	}
 	elseif ($className == 'NoreSources\SQL\NullData')
	{
		require_once(__DIR__ . '/Data.php');
	}
 	elseif ($className == 'NoreSources\SQL\BooleanData')
	{
		require_once(__DIR__ . '/Data.php');
	}
 	elseif ($className == 'NoreSources\SQL\FormattedData')
	{
		require_once(__DIR__ . '/Data.php');
	}
 	elseif ($className == 'NoreSources\SQL\StringData')
	{
		require_once(__DIR__ . '/Data.php');
	}
 	elseif ($className == 'NoreSources\SQL\NumberData')
	{
		require_once(__DIR__ . '/Data.php');
	}
 	elseif ($className == 'NoreSources\SQL\TimestampData')
	{
		require_once(__DIR__ . '/Data.php');
	}
 	elseif ($className == 'NoreSources\SQL\BinaryData')
	{
		require_once(__DIR__ . '/Data.php');
	}
 	elseif ($className == 'NoreSources\SQL\Table')
	{
		require_once(__DIR__ . '/Table.php');
	}
 	elseif ($className == 'NoreSources\SQL\MySQLTableManipulator')
	{
		require_once(__DIR__ . '/mysql/Datasource.php');
	}
 	elseif ($className == 'NoreSources\SQL\MySQLDatasource')
	{
		require_once(__DIR__ . '/mysql/Datasource.php');
	}
 	elseif ($className == 'NoreSources\SQL\MySQLStringData')
	{
		require_once(__DIR__ . '/mysql/Expressions.php');
	}
 	elseif ($className == 'NoreSources\SQL\MySQLBinaryData')
	{
		require_once(__DIR__ . '/mysql/Expressions.php');
	}
 	elseif ($className == 'NoreSources\SQL\MySQLEnumFieldValueValidator')
	{
		require_once(__DIR__ . '/mysql/FieldValidators.php');
	}
 	elseif ($className == 'NoreSources\SQL\MySQLSetFieldValueValidator')
	{
		require_once(__DIR__ . '/mysql/FieldValidators.php');
	}
 	elseif ($className == 'NoreSources\SQL\IExpression')
	{
		require_once(__DIR__ . '/BasicExpressions.php');
	}
 	elseif ($className == 'NoreSources\SQL\IAliasable')
	{
		require_once(__DIR__ . '/BasicExpressions.php');
	}
 	elseif ($className == 'NoreSources\SQL\SQLAlias')
	{
		require_once(__DIR__ . '/BasicExpressions.php');
	}
 	elseif ($className == 'NoreSources\SQL\SQLFunction')
	{
		require_once(__DIR__ . '/BasicExpressions.php');
	}
 	elseif ($className == 'NoreSources\SQL\SQLAnd')
	{
		require_once(__DIR__ . '/BasicExpressions.php');
	}
 	elseif ($className == 'NoreSources\SQL\SQLOr')
	{
		require_once(__DIR__ . '/BasicExpressions.php');
	}
 	elseif ($className == 'NoreSources\SQL\SQLNot')
	{
		require_once(__DIR__ . '/BasicExpressions.php');
	}
 	elseif ($className == 'NoreSources\SQL\SQLAs')
	{
		require_once(__DIR__ . '/BasicExpressions.php');
	}
 	elseif ($className == 'NoreSources\SQL\ITableFieldValueValidator')
	{
		require_once(__DIR__ . '/TableField.php');
	}
 	elseif ($className == 'NoreSources\SQL\ListedElementTableFieldValueValidator')
	{
		require_once(__DIR__ . '/TableField.php');
	}
 	elseif ($className == 'NoreSources\SQL\MultipleListedElementTableFieldValueValidator')
	{
		require_once(__DIR__ . '/TableField.php');
	}
 	elseif ($className == 'NoreSources\SQL\ITableFieldValueValidatorProvider')
	{
		require_once(__DIR__ . '/TableField.php');
	}
 	elseif ($className == 'NoreSources\SQL\ITableField')
	{
		require_once(__DIR__ . '/TableField.php');
	}
 	elseif ($className == 'NoreSources\SQL\StarColumn')
	{
		require_once(__DIR__ . '/TableField.php');
	}
 	elseif ($className == 'NoreSources\SQL\TableField')
	{
		require_once(__DIR__ . '/TableField.php');
	}
 	elseif ($className == 'NoreSources\SQL\InsertQuery')
	{
		require_once(__DIR__ . '/DataQueries.php');
	}
 	elseif ($className == 'NoreSources\SQL\UpdateQuery')
	{
		require_once(__DIR__ . '/DataQueries.php');
	}
 	elseif ($className == 'NoreSources\SQL\SelectQueryStaticValueColumn')
	{
		require_once(__DIR__ . '/DataQueries.php');
	}
 	elseif ($className == 'NoreSources\SQL\QueryConditionStatement')
	{
		require_once(__DIR__ . '/DataQueries.php');
	}
 	elseif ($className == 'NoreSources\SQL\WhereQueryConditionStatement')
	{
		require_once(__DIR__ . '/DataQueries.php');
	}
 	elseif ($className == 'NoreSources\SQL\HavingQueryConditionStatement')
	{
		require_once(__DIR__ . '/DataQueries.php');
	}
 	elseif ($className == 'NoreSources\SQL\DeleteQuery')
	{
		require_once(__DIR__ . '/DataQueries.php');
	}
 	elseif ($className == 'NoreSources\SQL\SelectQueryLimitStatement')
	{
		require_once(__DIR__ . '/DataQueries.php');
	}
 	elseif ($className == 'NoreSources\SQL\SelectQueryGroupByStatement')
	{
		require_once(__DIR__ . '/DataQueries.php');
	}
 	elseif ($className == 'NoreSources\SQL\ISelectQueryOrderByStatement')
	{
		require_once(__DIR__ . '/DataQueries.php');
	}
 	elseif ($className == 'NoreSources\SQL\SelectQueryRandomOrderByStatement')
	{
		require_once(__DIR__ . '/DataQueries.php');
	}
 	elseif ($className == 'NoreSources\SQL\SelectQueryOrderByStatement')
	{
		require_once(__DIR__ . '/DataQueries.php');
	}
 	elseif ($className == 'NoreSources\SQL\ISelectQueryJoin')
	{
		require_once(__DIR__ . '/DataQueries.php');
	}
 	elseif ($className == 'NoreSources\SQL\SelectQueryNaturalJoin')
	{
		require_once(__DIR__ . '/DataQueries.php');
	}
 	elseif ($className == 'NoreSources\SQL\SelectQueryJoin')
	{
		require_once(__DIR__ . '/DataQueries.php');
	}
 	elseif ($className == 'NoreSources\SQL\SelectQuery')
	{
		require_once(__DIR__ . '/DataQueries.php');
	}
 	elseif ($className == 'NoreSources\SQL\TruncateQuery')
	{
		require_once(__DIR__ . '/DataQueries.php');
	}
 	elseif ($className == 'NoreSources\SQL\StructureVersion')
	{
		require_once(__DIR__ . '/Structures.php');
	}
 	elseif ($className == 'NoreSources\SQL\StructureElement')
	{
		require_once(__DIR__ . '/Structures.php');
	}
 	elseif ($className == 'NoreSources\SQL\TableFieldStructure')
	{
		require_once(__DIR__ . '/Structures.php');
	}
 	elseif ($className == 'NoreSources\SQL\TableStructure')
	{
		require_once(__DIR__ . '/Structures.php');
	}
 	elseif ($className == 'NoreSources\SQL\DatabaseStructure')
	{
		require_once(__DIR__ . '/Structures.php');
	}
 	elseif ($className == 'NoreSources\SQL\DatasourceStructure')
	{
		require_once(__DIR__ . '/Structures.php');
	}
 	elseif ($className == 'NoreSources\SQL\SQLObject')
	{
		require_once(__DIR__ . '/Structures.php');
	}
 	elseif ($className == 'NoreSources\SQL\PDOBackend')
	{
		require_once(__DIR__ . '/pdo/PDO.php');
	}
 	elseif ($className == 'NoreSources\SQL\PostgreSQLDatasource')
	{
		require_once(__DIR__ . '/pgsql/Datasource.php');
	}
 	elseif ($className == 'NoreSources\SQL\PostgreSQLStringData')
	{
		require_once(__DIR__ . '/pgsql/Expressions.php');
	}
 	elseif ($className == 'NoreSources\SQL\PostgreSQLBinaryData')
	{
		require_once(__DIR__ . '/pgsql/Expressions.php');
	}
 	elseif ($className == 'NoreSources\SQL\ITransactionBlock')
	{
		require_once(__DIR__ . '/Datasource.php');
	}
 	elseif ($className == 'NoreSources\SQL\Datasource')
	{
		require_once(__DIR__ . '/Datasource.php');
	}
 	elseif ($className == 'NoreSources\SQL\DropTableQuery')
	{
		require_once(__DIR__ . '/StructureQueries.php');
	}
 	elseif ($className == 'NoreSources\SQL\RenameTableQuery')
	{
		require_once(__DIR__ . '/StructureQueries.php');
	}
 	elseif ($className == 'NoreSources\SQL\CreateTableQuery')
	{
		require_once(__DIR__ . '/StructureQueries.php');
	}
 	elseif ($className == 'NoreSources\SQL\SQLiteTableManipulator')
	{
		require_once(__DIR__ . '/sqlite/Datasource.php');
	}
 	elseif ($className == 'NoreSources\SQL\SQLiteDatabase')
	{
		require_once(__DIR__ . '/sqlite/Datasource.php');
	}
 	elseif ($className == 'NoreSources\SQL\SQLiteDatasource')
	{
		require_once(__DIR__ . '/sqlite/Datasource.php');
	}
 	elseif ($className == 'NoreSources\SQL\SQLiteStringData')
	{
		require_once(__DIR__ . '/sqlite/Expressions.php');
	}
 	elseif ($className == 'NoreSources\SQL\SQLiteBinaryData')
	{
		require_once(__DIR__ . '/sqlite/Expressions.php');
	}
 	elseif ($className == 'NoreSources\SQL\QueryResult')
	{
		require_once(__DIR__ . '/QueryResults.php');
	}
 	elseif ($className == 'NoreSources\SQL\Recordset')
	{
		require_once(__DIR__ . '/QueryResults.php');
	}
 	elseif ($className == 'NoreSources\SQL\InsertQueryResult')
	{
		require_once(__DIR__ . '/QueryResults.php');
	}
 	elseif ($className == 'NoreSources\SQL\UpdateQueryResult')
	{
		require_once(__DIR__ . '/QueryResults.php');
	}
 	elseif ($className == 'NoreSources\SQL\DeleteQueryResult')
	{
		require_once(__DIR__ . '/QueryResults.php');
	}
 	elseif ($className == 'NoreSources\SQL\IAliasedClone')
	{
		require_once(__DIR__ . '/base.php');
	}
 	elseif ($className == 'NoreSources\SQL\SQLIsNull')
	{
		require_once(__DIR__ . '/Expressions.php');
	}
 	elseif ($className == 'NoreSources\SQL\SQLIn')
	{
		require_once(__DIR__ . '/Expressions.php');
	}
 	elseif ($className == 'NoreSources\SQL\SQLSmartEquality')
	{
		require_once(__DIR__ . '/Expressions.php');
	}
 	elseif ($className == 'NoreSources\SQL\SQLBetween')
	{
		require_once(__DIR__ . '/Expressions.php');
	}
 	elseif ($className == 'NoreSources\SQL\AutoInterval')
	{
		require_once(__DIR__ . '/Expressions.php');
	}
 	elseif ($className == 'NoreSources\SQL\IQuery')
	{
		require_once(__DIR__ . '/QueryBase.php');
	}
 	elseif ($className == 'NoreSources\SQL\FormattedQuery')
	{
		require_once(__DIR__ . '/QueryBase.php');
	}
 	elseif ($className == 'NoreSources\SQL\TableQuery')
	{
		require_once(__DIR__ . '/QueryBase.php');
	}
 	elseif ($className == 'NoreSources\SQL\RecordManipulator')
	{
		require_once(__DIR__ . '/CRUD.php');
	}
 	elseif ($className == 'NoreSources\SQL\IDatabaseProvider')
	{
		require_once(__DIR__ . '/Providers.php');
	}
 	elseif ($className == 'NoreSources\SQL\ITableProvider')
	{
		require_once(__DIR__ . '/Providers.php');
	}
 	elseif ($className == 'NoreSources\SQL\ITableFieldProvider')
	{
		require_once(__DIR__ . '/Providers.php');
	}
 	elseif ($className == 'NoreSources\SQL\TableManipulator')
	{
		require_once(__DIR__ . '/Manipulators.php');
	}
 }
spl_autoload_register('autoload_NTY2ZjFmNzJjYmQ4NQ');
