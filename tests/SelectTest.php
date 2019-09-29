<?php
namespace NoreSources\SQL;

use PHPUnit\Framework\TestCase;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\ExpressionEvaluator as X;

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
		$context = new StatementContext($builder);
		$context->setPivot($tableStructure);
		$q = new SelectQuery($tableStructure, 't');
		
		$q->where (new InOperatorExpression( X::column('id'), [
				2, 4, 6, 8
		]), "name like 'Jean%'");

		$stream = new TokenStream();
		$q->tokenize($stream, $context);
		$sql = $builder->buildStatementData($stream);
		$sql = \SqlFormatter::format(strval($sql), false);

		$this->derivedFileManager->assertDerivedFile($sql, __METHOD__, null, 'sql');
	}

	public function testSelectCompanyTasks()
	{
		$structure = $this->datasources->get('Company');
		$tableStructure = $structure['ns_unittests']['Tasks'];
		$this->assertInstanceOf(TableStructure::class, $tableStructure);
		$builder = new Reference\StatementBuilder(
			K::BUILDER_SELECT_EXTENDED_RESULTCOLUMN_ALIAS_RESOLUTION);
		$context = new StatementContext($builder);
		$context->setPivot($tableStructure);
		$q = new SelectQuery($tableStructure, 't');

		$q->columns (
				['t.name', 'N'],
				'category', ['Employees.name' => 'AuthorName'],
				['e2.name', 'AssignedToName'])
				// Joins
			->join (K::JOIN_INNER, 'Employees', ['creator' => 'Employees.id'])
			->join (K::JOIN_INNER, [ 'Employees' => 'e2' ], ['assignedTo' => 'e2.id'])
			// Conditions
			->where('category = :userDefinedCategory')->
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
		$sql = $builder->buildStatementData($stream);
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