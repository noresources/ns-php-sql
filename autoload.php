<?php
spl_autoload_register(function($className) {
	if ($className == 'NoreSources\SQL\SQLite\Constants') {
		require_once(__DIR__ . '/SQLite/Constants.php');
	} elseif ($className == 'NoreSources\SQL\SQLite\StatementBuilder') {
		require_once(__DIR__ . '/SQLite/StatementBuilder.php');
	} elseif ($className == 'NoreSources\SQL\SQLite\Connection') {
		require_once(__DIR__ . '/SQLite/Connection.php');
	} elseif ($className == 'NoreSources\SQL\SQLite\PreparedStatement') {
		require_once(__DIR__ . '/SQLite/PreparedStatement.php');
	} elseif ($className == 'NoreSources\SQL\SQLite\Recordset') {
		require_once(__DIR__ . '/SQLite/Recordset.php');
	} elseif ($className == 'NoreSources\SQL\StructureException') {
		require_once(__DIR__ . '/Structures.php');
	} elseif ($className == 'NoreSources\SQL\StructureElement') {
		require_once(__DIR__ . '/Structures.php');
	} elseif ($className == 'NoreSources\SQL\TableColumnStructure') {
		require_once(__DIR__ . '/Structures.php');
	} elseif ($className == 'NoreSources\SQL\TableStructure') {
		require_once(__DIR__ . '/Structures.php');
	} elseif ($className == 'NoreSources\SQL\TableSetStructure') {
		require_once(__DIR__ . '/Structures.php');
	} elseif ($className == 'NoreSources\SQL\DatasourceStructure') {
		require_once(__DIR__ . '/Structures.php');
	} elseif ($className == 'NoreSources\SQL\StructureResolverException') {
		require_once(__DIR__ . '/Structures.php');
	} elseif ($className == 'NoreSources\SQL\StructureResolver') {
		require_once(__DIR__ . '/Structures.php');
	} elseif ($className == 'NoreSources\SQL\StatementException') {
		require_once(__DIR__ . '/Statements/Base.php');
	} elseif ($className == 'NoreSources\SQL\TableReference') {
		require_once(__DIR__ . '/Statements/Base.php');
	} elseif ($className == 'NoreSources\SQL\Statement') {
		require_once(__DIR__ . '/Statements/Base.php');
	} elseif ($className == 'NoreSources\SQL\ResultColumnReference') {
		require_once(__DIR__ . '/Statements/Select.php');
	} elseif ($className == 'NoreSources\SQL\JoinClause') {
		require_once(__DIR__ . '/Statements/Select.php');
	} elseif ($className == 'NoreSources\SQL\SelectQuery') {
		require_once(__DIR__ . '/Statements/Select.php');
	} elseif ($className == 'NoreSources\SQL\InsertQuery') {
		require_once(__DIR__ . '/Statements/Insert.php');
	} elseif ($className == 'NoreSources\SQL\DropTableQuery') {
		require_once(__DIR__ . '/Statements/Drop.php');
	} elseif ($className == 'NoreSources\SQL\UpdateQuery') {
		require_once(__DIR__ . '/Statements/Update.php');
	} elseif ($className == 'NoreSources\SQL\CreateTableQuery') {
		require_once(__DIR__ . '/Statements/CreateTable.php');
	} elseif ($className == 'NoreSources\SQL\StatementContextParameter') {
		require_once(__DIR__ . '/StatementContext.php');
	} elseif ($className == 'NoreSources\SQL\StatementContextParameterMap') {
		require_once(__DIR__ . '/StatementContext.php');
	} elseif ($className == 'NoreSources\SQL\StatementContext') {
		require_once(__DIR__ . '/StatementContext.php');
	} elseif ($className == 'NoreSources\SQL\Constants') {
		require_once(__DIR__ . '/Constants.php');
	} elseif ($className == 'NoreSources\SQL\StructureSerializer') {
		require_once(__DIR__ . '/StructureSerializer.php');
	} elseif ($className == 'NoreSources\SQL\JSONStructureSerializer') {
		require_once(__DIR__ . '/StructureSerializer.php');
	} elseif ($className == 'NoreSources\SQL\XMLStructureSerializer') {
		require_once(__DIR__ . '/StructureSerializer.php');
	} elseif ($className == 'NoreSources\SQL\StatementBuilder') {
		require_once(__DIR__ . '/StatementBuilder.php');
	} elseif ($className == 'NoreSources\SQL\GenericStatementBuilder') {
		require_once(__DIR__ . '/StatementBuilder.php');
	} elseif ($className == 'NoreSources\SQL\ConnectionException') {
		require_once(__DIR__ . '/Connection.php');
	} elseif ($className == 'NoreSources\SQL\Connection') {
		require_once(__DIR__ . '/Connection.php');
	} elseif ($className == 'NoreSources\SQL\ConnectionHelper') {
		require_once(__DIR__ . '/Connection.php');
	} elseif ($className == 'NoreSources\SQL\TableConstraint') {
		require_once(__DIR__ . '/TableConstraints.php');
	} elseif ($className == 'NoreSources\SQL\ColumnTableConstraint') {
		require_once(__DIR__ . '/TableConstraints.php');
	} elseif ($className == 'NoreSources\SQL\PrimaryKeyTableConstraint') {
		require_once(__DIR__ . '/TableConstraints.php');
	} elseif ($className == 'NoreSources\SQL\UniqueTableConstraint') {
		require_once(__DIR__ . '/TableConstraints.php');
	} elseif ($className == 'NoreSources\SQL\ForeignKeyTableConstraint') {
		require_once(__DIR__ . '/TableConstraints.php');
	} elseif ($className == 'NoreSources\SQL\ParameterArray') {
		require_once(__DIR__ . '/PreparedStatement.php');
	} elseif ($className == 'NoreSources\SQL\PreparedStatement') {
		require_once(__DIR__ . '/PreparedStatement.php');
	} elseif ($className == 'NoreSources\SQL\Expression') {
		require_once(__DIR__ . '/Expressions/Base.php');
	} elseif ($className == 'NoreSources\SQL\PreformattedExpression') {
		require_once(__DIR__ . '/Expressions/Base.php');
	} elseif ($className == 'NoreSources\SQL\KeywordExpression') {
		require_once(__DIR__ . '/Expressions/Base.php');
	} elseif ($className == 'NoreSources\SQL\LiteralExpression') {
		require_once(__DIR__ . '/Expressions/Base.php');
	} elseif ($className == 'NoreSources\SQL\ParameterExpression') {
		require_once(__DIR__ . '/Expressions/Base.php');
	} elseif ($className == 'NoreSources\SQL\ColumnExpression') {
		require_once(__DIR__ . '/Expressions/Base.php');
	} elseif ($className == 'NoreSources\SQL\TableExpression') {
		require_once(__DIR__ . '/Expressions/Base.php');
	} elseif ($className == 'NoreSources\SQL\CaseOptionExpression') {
		require_once(__DIR__ . '/Expressions/Case.php');
	} elseif ($className == 'NoreSources\SQL\CaseExpression') {
		require_once(__DIR__ . '/Expressions/Case.php');
	} elseif ($className == 'NoreSources\SQL\FunctionExpression') {
		require_once(__DIR__ . '/Expressions/Functions.php');
	} elseif ($className == 'NoreSources\SQL\ExpressionEvaluationException') {
		require_once(__DIR__ . '/Expressions/Evaluator.php');
	} elseif ($className == 'NoreSources\SQL\Evaluable') {
		require_once(__DIR__ . '/Expressions/Evaluator.php');
	} elseif ($className == 'NoreSources\SQL\ExpressionEvaluator') {
		require_once(__DIR__ . '/Expressions/Evaluator.php');
	} elseif ($className == 'NoreSources\SQL\PolishNotationOperation') {
		require_once(__DIR__ . '/Expressions/Evaluator.php');
	} elseif ($className == 'NoreSources\SQL\BinaryPolishNotationOperation') {
		require_once(__DIR__ . '/Expressions/Evaluator.php');
	} elseif ($className == 'NoreSources\SQL\UnaryPolishNotationOperation') {
		require_once(__DIR__ . '/Expressions/Evaluator.php');
	} elseif ($className == 'NoreSources\SQL\ListExpression') {
		require_once(__DIR__ . '/Expressions/Chunks.php');
	} elseif ($className == 'NoreSources\SQL\ParenthesisExpression') {
		require_once(__DIR__ . '/Expressions/Chunks.php');
	} elseif ($className == 'NoreSources\SQL\UnaryOperatorExpression') {
		require_once(__DIR__ . '/Expressions/Chunks.php');
	} elseif ($className == 'NoreSources\SQL\BinaryOperatorExpression') {
		require_once(__DIR__ . '/Expressions/Chunks.php');
	} elseif ($className == 'NoreSources\SQL\InOperatorExpression') {
		require_once(__DIR__ . '/Expressions/Chunks.php');
	} elseif ($className == 'NoreSources\SQL\BetweenExpression') {
		require_once(__DIR__ . '/Expressions/Chunks.php');
	} elseif ($className == 'NoreSources\SQL\Recordset') {
		require_once(__DIR__ . '/Recordset.php');
	}
});