<?php
namespace NoreSources\SQL;

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Expression\Evaluator as X;
use NoreSources\SQL\Expression\MemberOf;
use NoreSources\SQL\Expression\TokenStream;
use NoreSources\SQL\Statement\BuildContext;
use PHPUnit\Framework\TestCase;

final class SelectTest extends TestCase
{

	public function __construct()
	{
		parent::__construct();
		$this->derivedFileManager = new DerivedFileManager();
		$this->datasources = new DatasourceManager();
	}

	public function testSelectCompanyEmployees()
	{
		$structure = $this->datasources->get('Company');
		$tableStructure = $structure['ns_unittests']['Employees'];
		$this->assertInstanceOf(TableStructure::class, $tableStructure);
		$builder = new Reference\StatementBuilder();
		$context = new BuildContext($builder);
		$context->setPivot($tableStructure);
		$q = new Statement\SelectQuery($tableStructure, 't');

		$q->columns([
			'id',
			'identifier'
		]);
		$q->columns('name');

		$q->where(new MemberOf(X::column('id'), [
			2,
			4,
			6,
			8
		]), "name like 'Jean%'");

		$stream = new TokenStream();
		$q->tokenize($stream, $context);

		$this->assertEquals(2, $context->getResultColumnCount(),
			'Number of result column (after Builder::tokenize())');

		$result = $builder->finalize($stream, $context);
		$this->assertEquals($context, $result, 'Builder::finalzie() result');

		$this->assertEquals(2, $context->getResultColumnCount(),
			'Number of result column (after Builder::finalize())');

		$sql = \SqlFormatter::format(strval($result), false);

		$this->derivedFileManager->assertDerivedFile($sql, __METHOD__, null, 'sql');
	}

	public function testSelectCompanyTasks()
	{
		$structure = $this->datasources->get('Company');
		$tableStructure = $structure['ns_unittests']['Tasks'];
		$this->assertInstanceOf(TableStructure::class, $tableStructure);
		$builder = new Reference\StatementBuilder(
			K::BUILDER_SELECT_EXTENDED_RESULTCOLUMN_ALIAS_RESOLUTION);
		$context = new BuildContext($builder);
		$context->setPivot($tableStructure);
		$q = new Statement\SelectQuery($tableStructure, 't');

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
		// 	Ordery
		orderBy('substr(N, 3)')
			->
		// Limit
		limit(5, 3);

		$stream = new TokenStream();
		$q->tokenize($stream, $context);
		$builder->finalize($stream, $context);

		$this->assertEquals(K::QUERY_SELECT, $context->statementType, 'Statement type');
		$this->assertCount(4, $context->resultColumns, 'Number of result columns');

		$sql = \SqlFormatter::format(strval($context), false);

		$this->derivedFileManager->assertDerivedFile($sql, __METHOD__, null, 'sql');
	}

	public function testSubQueriesAndAliases()
	{
		$structure = $this->datasources->get('Company');
		$tablesetStructure = $structure['ns_unittests'];
		$this->assertInstanceOf(TableSetStructure::class, $tablesetStructure);
		$builder = new Reference\StatementBuilder();
		$context = new BuildContext($builder);
		$context->setPivot($tablesetStructure);

		$q = new Statement\SelectQuery('Employees', 'E');
		$q->columns([
			'id' => 'I'
		], [
			'name' => 'N'
		]);

		$sub = new Statement\SelectQuery('Hierarchy', 'E');
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
		$sql = $builder->finalize($stream, $context);
		$sql = \SqlFormatter::format(strval($sql), false);

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