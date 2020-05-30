<?php
namespace NoreSources\SQL;

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\ConnectionHelper;
use NoreSources\SQL\DBMS\Reference\ReferenceConnection;
use NoreSources\SQL\DBMS\Reference\ReferenceStatementBuilder;
use NoreSources\SQL\Expression\ExpressionHelper;
use NoreSources\SQL\Expression\Literal;
use NoreSources\SQL\Expression\MemberOf;
use NoreSources\SQL\Expression\TokenStream;
use NoreSources\SQL\Statement\StatementTokenStreamContext;
use NoreSources\SQL\Statement\Query\SelectQuery;
use NoreSources\Test\DatasourceManager;
use NoreSources\Test\DerivedFileManager;

final class SelectTest extends \PHPUnit\Framework\TestCase
{

	public function __construct($name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->derivedFileManager = new DerivedFileManager(__DIR__);
		$this->datasources = new DatasourceManager();
	}

	public function testSelectCompanyEmployees()
	{
		$structure = $this->datasources->get('Company');
		$tableStructure = $structure['ns_unittests']['Employees'];
		$this->assertInstanceOf(Structure\TableStructure::class, $tableStructure);
		$builder = new ReferenceStatementBuilder();
		$context = new StatementTokenStreamContext($builder);
		$context->setPivot($tableStructure);
		$q = new SelectQuery($tableStructure, 't');

		$q->columns([
			'id',
			'identifier'
		]);
		$q->columns('name');

		$q->where(new MemberOf(ExpressionHelper::column('id'), [
			2,
			4,
			6,
			8
		]), "name like 'Jean%'");

		$stream = new TokenStream();
		$q->tokenize($stream, $context);

		$this->assertCount(2, $context->getResultColumns(),
			'Number of result column (after Builder::tokenize())');

		$result = $builder->finalizeStatement($stream, $context);

		$this->assertCount(2, $result->getResultColumns(),
			'Number of result column (after Builder::finalize())');

		$this->assertEquals(K::QUERY_SELECT, $result->getStatementType(), 'Statement type');

		$sql = \SqlFormatter::format(strval($result), false);

		$this->derivedFileManager->assertDerivedFile($sql, __METHOD__, null, 'sql');
	}

	public function testStructurelessSelect()
	{
		$builder = new ReferenceStatementBuilder();
		$context = new StatementTokenStreamContext($builder);

		$select = new SelectQuery();
		$column = new Literal(true);
		$this->assertEquals(K::DATATYPE_BOOLEAN, $column->getExpressionDataType(),
			'Column value type');

		$select->columns($column);

		$stream = new TokenStream();
		$select->tokenize($stream, $context);
		$result = $builder->finalizeStatement($stream, $context);

		$this->assertEquals(K::QUERY_SELECT, $result->getStatementType(), 'Statement type');

		$this->derivedFileManager->assertDerivedFile(\strval($result), __METHOD__, 'true', 'sql');
	}

	public function testSelectCompanyTasks()
	{
		$structure = $this->datasources->get('Company');
		$tableStructure = $structure['ns_unittests']['Tasks'];
		$this->assertInstanceOf(Structure\TableStructure::class, $tableStructure);
		$builder = new ReferenceStatementBuilder(
			K::BUILDER_SELECT_EXTENDED_RESULTCOLUMN_ALIAS_RESOLUTION);
		$context = new StatementTokenStreamContext($builder);
		$context->setPivot($tableStructure);
		$q = new SelectQuery($tableStructure, 't');

		$q->columns([
			't.name',
			'N'
		], 'category', [
			'Employees.name' => 'AuthorName'
		], [
			'e2.name',
			'AssignedToName'
		])
			->
		// Joins
		join(K::JOIN_INNER, 'Employees', [
			'creator' => 'Employees.id'
		])
			->join(K::JOIN_INNER, [
			'Employees' => 'e2'
		], [
			'assignedTo' => 'e2.id'
		])
			->
		// Conditions
		where('category = :userDefinedCategory')
			->
		// Grouping
		groupBy('N', 'id')
			->
		// Ordery
		orderBy('substr(N, 3)')
			->
		// Limit
		limit(5, 3);

		$stream = new TokenStream();
		$q->tokenize($stream, $context);
		$result = $builder->finalizeStatement($stream, $context);

		$this->assertEquals(K::QUERY_SELECT, $result->getStatementType(), 'Statement type');
		$this->assertCount(4, $result->getResultColumns(), 'Number of result columns');

		$sql = \SqlFormatter::format(strval($result), false);

		$this->derivedFileManager->assertDerivedFile($sql, __METHOD__, null, 'sql');
	}

	public function testSubQueriesAndAliases()
	{
		$structure = $this->datasources->get('Company');
		$namespaceStructure = $structure['ns_unittests'];
		$this->assertInstanceOf(Structure\NamespaceStructure::class, $namespaceStructure);

		$builder = new ReferenceStatementBuilder();
		$context = new StatementTokenStreamContext($builder);
		$context->setPivot($namespaceStructure);

		$q = new SelectQuery('Employees', 'E');
		$q->columns([
			'id' => 'I'
		], [
			'name' => 'N'
		]);

		$sub = new SelectQuery('Hierarchy', 'E');
		$sub->columns([
			'manageeId' => 'N'
		]);
		$sub->where([
			'<' => [
				'managerId',
				10
			]
		]);

		$q->where([
			'gender' => "'M'"
		], [
			'in' => [
				'id',
				$sub
			]
		]);

		$stream = new TokenStream();
		$q->tokenize($stream, $context);
		$result = $builder->finalizeStatement($stream, $context);
		$sql = \SqlFormatter::format(strval($result), false);
		$this->derivedFileManager->assertDerivedFile($sql, __METHOD__, null, 'sql');
	}

	public function testUnion()
	{
		$structure = $this->datasources->get('Company');
		$tableStructure = $structure['ns_unittests']['Employees'];

		$a = new SelectQuery($tableStructure);
		$a->columns([
			'name' => 'n'
		]);
		$a->where([
			'gender' => "'M'"
		]);
		$a->orderBy('n');
		$b = new SelectQuery($tableStructure);
		$b->columns([
			'name' => 'm'
		]);
		$b->where([
			'>' => [
				'salary',
				1000
			]
		]);

		$a->union($b);

		$reference = new ReferenceConnection(array());
		$data = ConnectionHelper::buildStatement($reference, $a, $tableStructure);
		$sql = \SqlFormatter::format(strval($data), false);
		$this->derivedFileManager->assertDerivedFile($sql, __METHOD__, null, 'sql');
	}

	/**
	 * select name from (SELECT name from Employees);
	 */
	public function testInlineDataRowContainers()
	{
		$this->assertTrue(true, 'Temp');
		$connection = ConnectionHelper::createConnection();
		$this->assertInstanceOf(ReferenceConnection::class, $connection,
			'Reference connection instance');

		$structure = $this->datasources->get('Company');
		$employeesStructure = $structure['ns_unittests']['Employees'];

		/**
		 *
		 * @var \NoreSources\SQL\Statement\Query\SelectQuery $innerSelect
		 */
		$innerSelect = $connection->getStatementFactory()->newStatement(K::QUERY_SELECT);
		$innerSelect->from($employeesStructure);
		$innerSelect->columns('id', 'name')->where([
			'gender' => ':g'
		]);
		$innerData = ConnectionHelper::buildStatement($connection, $innerSelect, $employeesStructure);
		$this->assertCount(2, $innerData->getResultColumns(), 'Inner query result columns');
		$this->assertCount(1, $innerData->getParameters(), 'Inner parameter count');

		/**
		 *
		 * @var SelectQuery $innerJoinSelect
		 */
		$innerJoinSelect = $connection->getStatementFactory()->newStatement(K::QUERY_SELECT);
		$innerJoinSelect->from('Hierarchy');

		/**
		 *
		 * @var \NoreSources\SQL\Statement\Query\SelectQuery $outerSelect
		 */
		$outerSelect = $connection->getStatementFactory()->newStatement(K::QUERY_SELECT);
		$outerSelect->from($innerSelect, 'e')
			->columns('id', 'e.name', 'H.manageeId')
			->join(K::JOIN_INNER, [
			$innerJoinSelect,
			'H'
		], [
			'id' => 'H.managerId'
		]);

		$data = ConnectionHelper::buildStatement($connection, $outerSelect, $structure);
		$this->assertCount(1, $data->getParameters(), 'Outer parameter count');

		$sql = \SqlFormatter::format(strval($data), false);
		//
		$this->derivedFileManager->assertDerivedFile($sql, __METHOD__, null, 'sql');
	}

	/**
	 *
	 * @var DatasourceManager
	 */
	private $datasources;

	/**
	 *
	 * @var DerivedFileManager
	 */
	private $derivedFileManager;
}