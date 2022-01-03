<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL;

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\Reference\ReferenceConnection;
use NoreSources\SQL\DBMS\Reference\ReferencePlatform;
use NoreSources\SQL\Structure\NamespaceStructure;
use NoreSources\SQL\Syntax\Column;
use NoreSources\SQL\Syntax\Data;
use NoreSources\SQL\Syntax\MemberOf;
use NoreSources\SQL\Syntax\Statement\StatementBuilder;
use NoreSources\SQL\Syntax\Statement\StatementTokenStreamContext;
use NoreSources\SQL\Syntax\Statement\Query\SelectQuery;
use NoreSources\Test\DatasourceManager;
use NoreSources\Test\DerivedFileTestTrait;
use NoreSources\Test\SqlFormatter;
use PHPUnit\Framework\TestCase;

final class SelectTest extends TestCase
{

	use DerivedFileTestTrait;

	public function __construct($name = null, array $data = [],
		$dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->initializeDerivedFileTest(__DIR__);
		$this->datasources = new DatasourceManager();
	}

	public function testSelectCompanyEmployees()
	{
		$structure = $this->datasources->get('Company');
		$tableStructure = $structure['ns_unittests']['Employees'];
		$this->assertInstanceOf(Structure\TableStructure::class,
			$tableStructure);
		$platform = new ReferencePlatform();
		$q = new SelectQuery($tableStructure, 't');

		$q->columns([
			'id',
			'identifier'
		]);
		$q->columns('name');

		$q->where(
			[
				'or' => [
					new MemberOf(new Column('id'), [
						2,
						4,
						6,
						8
					]),
					"name like 'Jean%'"
				]
			])->where("name != 'Jean-Claude'");

		$result =  StatementBuilder::getInstance() ($q, $platform, $tableStructure);

		$this->assertCount(2, $result->getResultColumns(),
			'Number of result column');

		$this->assertEquals(K::QUERY_SELECT, $result->getStatementType(),
			'Statement type');

		$sql = SqlFormatter::format(strval($result), false);

		$this->assertDerivedFile($sql, __METHOD__, null, 'sql');
	}

	public function testStructurelessSelect()
	{
		$platform = new ReferencePlatform();

		$select = new SelectQuery();
		$column = new Data(true);
		$this->assertEquals(K::DATATYPE_BOOLEAN, $column->getDataType(),
			'Column value type');

		$select->columns($column);

		$result =  StatementBuilder::getInstance() ($select, $platform);

		$this->assertEquals(K::QUERY_SELECT, $result->getStatementType(),
			'Statement type');

		$this->assertDerivedFile(\strval($result), __METHOD__, 'true',
			'sql');
	}

	public function testSelectCompanyTasks()
	{
		$structure = $this->datasources->get('Company');
		$tableStructure = $structure['ns_unittests']['Tasks'];
		$this->assertInstanceOf(Structure\TableStructure::class,
			$tableStructure);
		$platform = new ReferencePlatform([],
			[
				'with extended alias support' => [
					[
						K::FEATURE_SELECT,
						K::FEATURE_EXTENDED_RESULTCOLUMN_RESOLUTION
					],
					true
				]
			]);
		StatementBuilder::getInstance(); // IDO workaround
		$context = new StatementTokenStreamContext($platform);
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

		$result =  StatementBuilder::getInstance() ($q, $platform, $tableStructure);

		$this->assertEquals(K::QUERY_SELECT, $result->getStatementType(),
			'Statement type');
		$this->assertCount(4, $result->getResultColumns(),
			'Number of result columns');

		$sql = SqlFormatter::format(strval($result), false);

		$this->assertDerivedFile($sql, __METHOD__, null, 'sql');
	}

	public function testSubQueriesAndAliases()
	{
		$structure = $this->datasources->get('Company');
		$namespaceStructure = $structure['ns_unittests'];
		$this->assertInstanceOf(Structure\NamespaceStructure::class,
			$namespaceStructure);

		$platform = new ReferencePlatform();

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

		$result =  StatementBuilder::getInstance() ($q, $platform, $namespaceStructure);

		$sql = SqlFormatter::format(strval($result), false);
		$this->assertDerivedFile($sql, __METHOD__, null, 'sql');
	}

	public function testPolishNotation()
	{
		$env = new Environment();
		$connection = $env->getConnection();
		$factory = $connection->getPlatform();

		/**
		 *
		 * @var SelectQuery $select
		 */
		$select = $factory->newStatement(SelectQuery::class);

		$this->assertInstanceOf(SelectQuery::class, $select);

		$structure = $this->datasources->get('Company')['ns_unittests'];
		$this->assertInstanceOf(NamespaceStructure::class, $structure);

		$select->from('Employees')->where(
			[
				'!in' => [
					'gender',
					"'M'",
					"'F'"
				]
			]);

		$data = $env->prepareStatement($select, $structure);
		$sql = SqlFormatter::format(strval($data), false);
		$this->assertDerivedFile($sql, __METHOD__, 'not in', 'sql');
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

		$env = new Environment();
		$data = $env->prepareStatement($a, $tableStructure);
		$sql = SqlFormatter::format(strval($data), false);
		$this->assertDerivedFile($sql, __METHOD__, null, 'sql');
	}

	/**
	 * select name from (SELECT name from Employees);
	 */
	public function testInlineDataRowContainers()
	{
		$env = new Environment();
		$connection = $env->getConnection();
		$this->assertInstanceOf(ReferenceConnection::class, $connection,
			'Reference connection instance');

		$structure = $this->datasources->get('Company');
		$employeesStructure = $structure['ns_unittests']['Employees'];

		/**
		 *
		 * @var \NoreSources\SQL\Syntax\Statement\Query\SelectQuery $innerSelect
		 */
		$innerSelect = $connection->getPlatform()->newStatement(
			SelectQuery::class);
		$innerSelect->from($employeesStructure);
		$innerSelect->columns('id', 'name')->where([
			'gender' => ':g'
		]);
		$innerData = $env->prepareStatement($innerSelect,
			$employeesStructure);
		$this->assertCount(2, $innerData->getResultColumns(),
			'Inner query result columns');
		$this->assertCount(1, $innerData->getParameters(),
			'Inner parameter count');

		/**
		 *
		 * @var SelectQuery $innerJoinSelect
		 */
		$innerJoinSelect = $connection->getPlatform()->newStatement(
			SelectQuery::class);
		$innerJoinSelect->from('Hierarchy');

		/**
		 *
		 * @var \NoreSources\SQL\Syntax\Statement\Query\SelectQuery $outerSelect
		 */
		$outerSelect = $connection->getPlatform()->newStatement(
			SelectQuery::class);
		$outerSelect->from($innerSelect, 'e')
			->columns('id', 'e.name', 'H.manageeId')
			->join(K::JOIN_INNER, [
			$innerJoinSelect,
			'H'
		], [
			'id' => 'H.managerId'
		]);

		$data = $env->prepareStatement($outerSelect, $structure);
		$this->assertCount(1, $data->getParameters(),
			'Outer parameter count');

		$sql = SqlFormatter::format(strval($data), false);
		//
		$this->assertDerivedFile($sql, __METHOD__, null, 'sql');
	}

	/**
	 *
	 * @var DatasourceManager
	 */
	private $datasources;
}