<?php
function autoload_NWNiY2E5MTk5Y2ZhMA($className)
{
	if ($className == 'NoreSources\SQL\ResultColumnReference')
	{
		require_once(__DIR__ . '/Statements/Select.php');
	}
 	elseif ($className == 'NoreSources\SQL\JoinClause')
	{
		require_once(__DIR__ . '/Statements/Select.php');
	}
 	elseif ($className == 'NoreSources\SQL\OrderBy')
	{
		require_once(__DIR__ . '/Statements/Select.php');
	}
 	elseif ($className == 'NoreSources\SQL\SelectQuery')
	{
		require_once(__DIR__ . '/Statements/Select.php');
	}
 	elseif ($className == 'NoreSources\SQL\StructureQueryResolver')
	{
		require_once(__DIR__ . '/Statements/Base.php');
	}
 	elseif ($className == 'NoreSources\SQL\StructureQueryDescription')
	{
		require_once(__DIR__ . '/Statements/Base.php');
	}
 	elseif ($className == 'NoreSources\SQL\TableReference')
	{
		require_once(__DIR__ . '/Statements/Base.php');
	}
 	elseif ($className == 'NoreSources\SQL\K')
	{
		require_once(__DIR__ . '/Constants.php');
	}
 	elseif ($className == 'NoreSources\SQL\StatementBuilder')
	{
		require_once(__DIR__ . '/StatementBuilder.php');
	}
 	elseif ($className == 'NoreSources\SQL\GenericStatementBuilder')
	{
		require_once(__DIR__ . '/StatementBuilder.php');
	}
 	elseif ($className == 'Ferno\Loco\GrammarException')
	{
		require_once(__DIR__ . '/vendor/Loco.php');
	}
 	elseif ($className == 'Ferno\Loco\ParseFailureException')
	{
		require_once(__DIR__ . '/vendor/Loco.php');
	}
 	elseif ($className == 'Ferno\Loco\MonoParser')
	{
		require_once(__DIR__ . '/vendor/Loco.php');
	}
 	elseif ($className == 'Ferno\Loco\StaticParser')
	{
		require_once(__DIR__ . '/vendor/Loco.php');
	}
 	elseif ($className == 'Ferno\Loco\EmptyParser')
	{
		require_once(__DIR__ . '/vendor/Loco.php');
	}
 	elseif ($className == 'Ferno\Loco\StringParser')
	{
		require_once(__DIR__ . '/vendor/Loco.php');
	}
 	elseif ($className == 'Ferno\Loco\RegexParser')
	{
		require_once(__DIR__ . '/vendor/Loco.php');
	}
 	elseif ($className == 'Ferno\Loco\Utf8Parser')
	{
		require_once(__DIR__ . '/vendor/Loco.php');
	}
 	elseif ($className == 'Ferno\Loco\LazyAltParser')
	{
		require_once(__DIR__ . '/vendor/Loco.php');
	}
 	elseif ($className == 'Ferno\Loco\GreedyMultiParser')
	{
		require_once(__DIR__ . '/vendor/Loco.php');
	}
 	elseif ($className == 'Ferno\Loco\GreedyStarParser')
	{
		require_once(__DIR__ . '/vendor/Loco.php');
	}
 	elseif ($className == 'Ferno\Loco\ConcParser')
	{
		require_once(__DIR__ . '/vendor/Loco.php');
	}
 	elseif ($className == 'Ferno\Loco\Grammar')
	{
		require_once(__DIR__ . '/vendor/Loco.php');
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
 	elseif ($className == 'NoreSources\SQL\StructureResolverException')
	{
		require_once(__DIR__ . '/Structures.php');
	}
 	elseif ($className == 'NoreSources\SQL\StructureResolver')
	{
		require_once(__DIR__ . '/Structures.php');
	}
 	elseif ($className == 'NoreSources\SQL\PDOBackend')
	{
		require_once(__DIR__ . '/pdo/PDO.php');
	}
 	elseif ($className == 'NoreSources\SQL\Expression')
	{
		require_once(__DIR__ . '/Expressions.php');
	}
 	elseif ($className == 'NoreSources\SQL\PreformattedExpression')
	{
		require_once(__DIR__ . '/Expressions.php');
	}
 	elseif ($className == 'NoreSources\SQL\LiteralExpression')
	{
		require_once(__DIR__ . '/Expressions.php');
	}
 	elseif ($className == 'NoreSources\SQL\ParameterExpression')
	{
		require_once(__DIR__ . '/Expressions.php');
	}
 	elseif ($className == 'NoreSources\SQL\ColumnExpression')
	{
		require_once(__DIR__ . '/Expressions.php');
	}
 	elseif ($className == 'NoreSources\SQL\TableExpression')
	{
		require_once(__DIR__ . '/Expressions.php');
	}
 	elseif ($className == 'NoreSources\SQL\FunctionExpression')
	{
		require_once(__DIR__ . '/Expressions.php');
	}
 	elseif ($className == 'NoreSources\SQL\ListExpression')
	{
		require_once(__DIR__ . '/Expressions.php');
	}
 	elseif ($className == 'NoreSources\SQL\ParenthesisExpression')
	{
		require_once(__DIR__ . '/Expressions.php');
	}
 	elseif ($className == 'NoreSources\SQL\UnaryOperatorExpression')
	{
		require_once(__DIR__ . '/Expressions.php');
	}
 	elseif ($className == 'NoreSources\SQL\BinaryOperatorExpression')
	{
		require_once(__DIR__ . '/Expressions.php');
	}
 	elseif ($className == 'NoreSources\SQL\CaseOptionExpression')
	{
		require_once(__DIR__ . '/Expressions.php');
	}
 	elseif ($className == 'NoreSources\SQL\CaseExpression')
	{
		require_once(__DIR__ . '/Expressions.php');
	}
 	elseif ($className == 'NoreSources\SQL\ExpressionEvaluator')
	{
		require_once(__DIR__ . '/Expressions.php');
	}
 }
spl_autoload_register('autoload_NWNiY2E5MTk5Y2ZhMA');
