<?php
spl_autoload_register(function($className) {
	$className = strtolower ($className);
	$classMap = array (
		'noresources\sql\statementexception' => 'src/Statements/Base.php',
		'noresources\sql\statementparameteriterator' => 'src/Statements/Base.php',
		'noresources\sql\statementparametermap' => 'src/Statements/Base.php',
		'noresources\sql\resultcolumn' => 'src/Statements/Base.php',
		'noresources\sql\resultcolumnmap' => 'src/Statements/Base.php',
		'noresources\sql\statementinputdata' => 'src/Statements/Base.php',
		'noresources\sql\statementinputdatatrait' => 'src/Statements/Base.php',
		'noresources\sql\statementoutputdata' => 'src/Statements/Base.php',
		'noresources\sql\statementoutputdatatrait' => 'src/Statements/Base.php',
		'noresources\sql\tablereference' => 'src/Statements/Base.php',
		'noresources\sql\statement' => 'src/Statements/Base.php',
		'noresources\sql\columnvaluetrait' => 'src/Statements/Base.php',
		'noresources\sql\droptablequery' => 'src/Statements/DropTable.php',
		'noresources\sql\statementcontext' => 'src/Statements/Context.php',
		'noresources\sql\resultcolumnreference' => 'src/Statements/Select.php',
		'noresources\sql\joinclause' => 'src/Statements/Select.php',
		'noresources\sql\selectquery' => 'src/Statements/Select.php',
		'noresources\sql\statementbuilder' => 'src/Statements/Builder.php',
		'noresources\sql\insertquery' => 'src/Statements/Insert.php',
		'noresources\sql\updatequery' => 'src/Statements/Update.php',
		'noresources\sql\createtablequery' => 'src/Statements/CreateTable.php',
		'noresources\sql\deletequery' => 'src/Statements/Delete.php',
		'noresources\sql\constants' => 'src/Constants.php',
		'noresources\sql\sqlite\constants' => 'src/DBMS/SQLite/Constants.php',
		'noresources\sql\sqlite\statementbuilder' => 'src/DBMS/SQLite/StatementBuilder.php',
		'noresources\sql\sqlite\connection' => 'src/DBMS/SQLite/Connection.php',
		'noresources\sql\sqlite\preparedstatement' => 'src/DBMS/SQLite/PreparedStatement.php',
		'noresources\sql\sqlite\recordset' => 'src/DBMS/SQLite/Recordset.php',
		'noresources\sql\pdo\constants' => 'src/DBMS/PDO/Constants.php',
		'noresources\sql\pdo\statementbuilder' => 'src/DBMS/PDO/StatementBuilder.php',
		'noresources\sql\pdo\connection' => 'src/DBMS/PDO/Connection.php',
		'noresources\sql\pdo\preparedstatement' => 'src/DBMS/PDO/PreparedStatement.php',
		'noresources\sql\pdo\recordset' => 'src/DBMS/PDO/Recordset.php',
		'noresources\sql\reference\statementbuilder' => 'src/DBMS/Reference/StatementBuilder.php',
		'noresources\sql\reference\connection' => 'src/DBMS/Reference/Connection.php',
		'noresources\sql\reference\preparedstatement' => 'src/DBMS/Reference/PreparedStatement.php',
		'noresources\sql\connectionexception' => 'src/DBMS/Connection.php',
		'noresources\sql\connection' => 'src/DBMS/Connection.php',
		'noresources\sql\connectionhelper' => 'src/DBMS/Connection.php',
		'noresources\sql\parameterarray' => 'src/DBMS/PreparedStatement.php',
		'noresources\sql\preparedstatement' => 'src/DBMS/PreparedStatement.php',
		'noresources\sql\structureexception' => 'src/Structure/Structures.php',
		'noresources\sql\structureelement' => 'src/Structure/Structures.php',
		'noresources\sql\tablecolumnstructure' => 'src/Structure/Structures.php',
		'noresources\sql\tablestructure' => 'src/Structure/Structures.php',
		'noresources\sql\tablesetstructure' => 'src/Structure/Structures.php',
		'noresources\sql\datasourcestructure' => 'src/Structure/Structures.php',
		'noresources\sql\structureserializer' => 'src/Structure/Serializers.php',
		'noresources\sql\jsonstructureserializer' => 'src/Structure/Serializers.php',
		'noresources\sql\xmlstructureserializer' => 'src/Structure/Serializers.php',
		'noresources\sql\structureresolverexception' => 'src/Structure/Resolver.php',
		'noresources\sql\structureresolvercontext' => 'src/Structure/Resolver.php',
		'noresources\sql\structureresolver' => 'src/Structure/Resolver.php',
		'noresources\sql\tableconstraint' => 'src/Structure/TableConstraints.php',
		'noresources\sql\columntableconstraint' => 'src/Structure/TableConstraints.php',
		'noresources\sql\primarykeytableconstraint' => 'src/Structure/TableConstraints.php',
		'noresources\sql\uniquetableconstraint' => 'src/Structure/TableConstraints.php',
		'noresources\sql\foreignkeytableconstraint' => 'src/Structure/TableConstraints.php',
		'noresources\sql\caseoptionexpression' => 'src/Syntax/Case.php',
		'noresources\sql\caseexpression' => 'src/Syntax/Case.php',
		'noresources\sql\functionexpression' => 'src/Syntax/Functions.php',
		'noresources\sql\expressionevaluationexception' => 'src/Syntax/Evaluator.php',
		'noresources\sql\evaluable' => 'src/Syntax/Evaluator.php',
		'noresources\sql\expressionevaluator' => 'src/Syntax/Evaluator.php',
		'noresources\sql\polishnotationoperation' => 'src/Syntax/Evaluator.php',
		'noresources\sql\binarypolishnotationoperation' => 'src/Syntax/Evaluator.php',
		'noresources\sql\unarypolishnotationoperation' => 'src/Syntax/Evaluator.php',
		'noresources\sql\parenthesisexpression' => 'src/Syntax/Surrounding.php',
		'noresources\sql\expression' => 'src/Syntax/Expression.php',
		'noresources\sql\keywordexpression' => 'src/Syntax/Expression.php',
		'noresources\sql\literalexpression' => 'src/Syntax/Expression.php',
		'noresources\sql\parameterexpression' => 'src/Syntax/Expression.php',
		'noresources\sql\columnexpression' => 'src/Syntax/Expression.php',
		'noresources\sql\tableexpression' => 'src/Syntax/Expression.php',
		'noresources\sql\unaryoperatorexpression' => 'src/Syntax/Operators.php',
		'noresources\sql\binaryoperatorexpression' => 'src/Syntax/Operators.php',
		'noresources\sql\inoperatorexpression' => 'src/Syntax/Operators.php',
		'noresources\sql\betweenexpression' => 'src/Syntax/Operators.php',
		'noresources\sql\listexpression' => 'src/Syntax/List.php',
		'noresources\sql\tokenizable' => 'src/Syntax/Tokens.php',
		'noresources\sql\tokenstream' => 'src/Syntax/Tokens.php',
		'noresources\sql\queryresult' => 'src/Results/QuesyResults.php',
		'noresources\sql\rowmodificationqueryresult' => 'src/Results/QuesyResults.php',
		'noresources\sql\insertionqueryresult' => 'src/Results/QuesyResults.php',
		'noresources\sql\genericrowmodificationqueryresult' => 'src/Results/QuesyResults.php',
		'noresources\sql\genericinsertionqueryresult' => 'src/Results/QuesyResults.php',
		'noresources\sql\recordsetexception' => 'src/Results/Recordset.php',
		'noresources\sql\recordset' => 'src/Results/Recordset.php'
	); // classMap

	if (\array_key_exists ($className, $classMap)) {
		require_once(__DIR__ . '/' . $classMap[$className]);
	}
});