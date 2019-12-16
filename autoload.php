<?php
spl_autoload_register(function($className) {
	$className = strtolower ($className);
	$classMap = array (
		'noresources\sql\constants' => 'src/Constants.php',
		'noresources\sql\sqlite\constants' => 'src/DBMS/SQLite/Constants.php',
		'noresources\sql\sqlite\statementbuilder' => 'src/DBMS/SQLite/StatementBuilder.php',
		'noresources\sql\sqlite\connectionexception' => 'src/DBMS/SQLite/Connection.php',
		'noresources\sql\sqlite\connection' => 'src/DBMS/SQLite/Connection.php',
		'noresources\sql\sqlite\preparedstatement' => 'src/DBMS/SQLite/PreparedStatement.php',
		'noresources\sql\sqlite\recordset' => 'src/DBMS/SQLite/Recordset.php',
		'noresources\sql\dataunserializer' => 'src/DBMS/DataSerialization.php',
		'noresources\sql\dataserializer' => 'src/DBMS/DataSerialization.php',
		'noresources\sql\pdo\constants' => 'src/DBMS/PDO/Constants.php',
		'noresources\sql\pdo\statementbuilder' => 'src/DBMS/PDO/StatementBuilder.php',
		'noresources\sql\pdo\connection' => 'src/DBMS/PDO/Connection.php',
		'noresources\sql\pdo\preparedstatement' => 'src/DBMS/PDO/PreparedStatement.php',
		'noresources\sql\pdo\recordset' => 'src/DBMS/PDO/Recordset.php',
		'noresources\sql\statementparameterarray' => 'src/DBMS/StatementParameterArray.php',
		'noresources\sql\reference\statementbuilder' => 'src/DBMS/Reference/StatementBuilder.php',
		'noresources\sql\reference\connection' => 'src/DBMS/Reference/Connection.php',
		'noresources\sql\reference\preparedstatement' => 'src/DBMS/Reference/PreparedStatement.php',
		'noresources\sql\connection' => 'src/DBMS/Connection.php',
		'noresources\sql\connectionstructuretrait' => 'src/DBMS/Connection.php',
		'noresources\sql\connectionhelper' => 'src/DBMS/ConnectionHelper.php',
		'noresources\sql\preparedstatement' => 'src/DBMS/PreparedStatement.php',
		'noresources\sql\connectionexception' => 'src/DBMS/ConnectionException.php',
		'noresources\sql\expression\tokenizable' => 'src/Expression/Tokenizable.php',
		'noresources\sql\expression\between' => 'src/Expression/Between.php',
		'noresources\sql\expression\memberof' => 'src/Expression/MemberOf.php',
		'noresources\sql\expression\column' => 'src/Expression/Column.php',
		'noresources\sql\expression\helper' => 'src/Expression/Helper.php',
		'noresources\sql\expression\tablereference' => 'src/Expression/TableReference.php',
		'noresources\sql\expression\keyword' => 'src/Expression/Keyword.php',
		'noresources\sql\expression\tokenstream' => 'src/Expression/TokenStream.php',
		'noresources\sql\expression\evaluatorexceptioion' => 'src/Expression/Evaluator.php',
		'noresources\sql\expression\evaluator' => 'src/Expression/Evaluator.php',
		'noresources\sql\expression\polishnotationoperation' => 'src/Expression/Evaluator.php',
		'noresources\sql\expression\binarypolishnotationoperation' => 'src/Expression/Evaluator.php',
		'noresources\sql\expression\unarypolishnotationoperation' => 'src/Expression/Evaluator.php',
		'noresources\sql\expression\binaryoperation' => 'src/Expression/BinaryOperation.php',
		'noresources\sql\expression\table' => 'src/Expression/Table.php',
		'noresources\sql\expression\alternative' => 'src/Expression/AlternativeList.php',
		'noresources\sql\expression\alternativelist' => 'src/Expression/AlternativeList.php',
		'noresources\sql\expression\expression' => 'src/Expression/Expression.php',
		'noresources\sql\expression\structureelementidentifier' => 'src/Expression/StructureElementIdentifier.php',
		'noresources\sql\expression\surround' => 'src/Expression/Surround.php',
		'noresources\sql\expression\parameter' => 'src/Expression/Parameter.php',
		'noresources\sql\expression\unaryoperation' => 'src/Expression/UnaryOperation.php',
		'noresources\sql\expression\value' => 'src/Expression/Value.php',
		'noresources\sql\expression\procedure' => 'src/Expression/Procedure.php',
		'noresources\sql\expression\expressionreturntype' => 'src/Expression/ExpressionReturnType.php',
		'noresources\sql\structureexception' => 'src/Structure/Structures.php',
		'noresources\sql\structureelement' => 'src/Structure/Structures.php',
		'noresources\sql\tablecolumnstructure' => 'src/Structure/Structures.php',
		'noresources\sql\tablestructure' => 'src/Structure/Structures.php',
		'noresources\sql\tablesetstructure' => 'src/Structure/Structures.php',
		'noresources\sql\datasourcestructure' => 'src/Structure/Structures.php',
		'noresources\sql\structureserializer' => 'src/Structure/Serializers.php',
		'noresources\sql\jsonstructureserializer' => 'src/Structure/Serializers.php',
		'noresources\sql\xmlstructureserializer' => 'src/Structure/Serializers.php',
		'noresources\sql\structureserializerfactory' => 'src/Structure/Serializers.php',
		'noresources\sql\columnpropertymap' => 'src/Structure/ColumnProperty.php',
		'noresources\sql\columnpropertymaptrait' => 'src/Structure/ColumnProperty.php',
		'noresources\sql\arraycolumnpropertymap' => 'src/Structure/ColumnProperty.php',
		'noresources\sql\columnpropertydefault' => 'src/Structure/ColumnProperty.php',
		'noresources\sql\structureresolverexception' => 'src/Structure/Resolver.php',
		'noresources\sql\structureresolvercontext' => 'src/Structure/Resolver.php',
		'noresources\sql\structureresolver' => 'src/Structure/Resolver.php',
		'noresources\sql\tableconstraint' => 'src/Structure/TableConstraints.php',
		'noresources\sql\columntableconstraint' => 'src/Structure/TableConstraints.php',
		'noresources\sql\primarykeytableconstraint' => 'src/Structure/TableConstraints.php',
		'noresources\sql\uniquetableconstraint' => 'src/Structure/TableConstraints.php',
		'noresources\sql\foreignkeytableconstraint' => 'src/Structure/TableConstraints.php',
		'noresources\sql\statement\resultcolumn' => 'src/Statement/ResultColumn.php',
		'noresources\sql\statement\resultcolumnmap' => 'src/Statement/ResultColumn.php',
		'noresources\sql\statement\resultcolumnreference' => 'src/Statement/SelectQuery.php',
		'noresources\sql\statement\joinclause' => 'src/Statement/SelectQuery.php',
		'noresources\sql\statement\selectquery' => 'src/Statement/SelectQuery.php',
		'noresources\sql\statement\updatequery' => 'src/Statement/UpdateQuery.php',
		'noresources\sql\statement\createtablequery' => 'src/Statement/CreateTableQuery.php',
		'noresources\sql\statement\columnvaluetrait' => 'src/Statement/ColumnValueTrait.php',
		'noresources\sql\statement\droptablequery' => 'src/Statement/DropTableQuery.php',
		'noresources\sql\statement\parameteriterator' => 'src/Statement/ParameterMap.php',
		'noresources\sql\statement\parametermap' => 'src/Statement/ParameterMap.php',
		'noresources\sql\statement\insertquery' => 'src/Statement/InsertQuery.php',
		'noresources\sql\statement\deletequery' => 'src/Statement/DeleteQuery.php',
		'noresources\sql\statement\outputdata' => 'src/Statement/OutputData.php',
		'noresources\sql\statement\outputdatatrait' => 'src/Statement/OutputData.php',
		'noresources\sql\statement\builder' => 'src/Statement/Builder.php',
		'noresources\sql\statement\buildcontext' => 'src/Statement/BuildContext.php',
		'noresources\sql\statement\statementexception' => 'src/Statement/Statement.php',
		'noresources\sql\statement\statement' => 'src/Statement/Statement.php',
		'noresources\sql\statement\inputdata' => 'src/Statement/InputData.php',
		'noresources\sql\statement\inputdatatrait' => 'src/Statement/InputData.php',
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