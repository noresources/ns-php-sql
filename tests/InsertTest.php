<?php

namespace NoreSources\SQL;

use PHPUnit\Framework\TestCase;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\ExpressionEvaluator as X;

final class InsertTest extends TestCase
{

	public function __construct()
	{
		parent::__construct();
		$this->datasources = new DatasourceManager();
		$this->derivedFileManager = new DerivedFileManager();
	}

	public function testInsertBasic()
	{
		$structure = $this->datasources->get('types');
		$t = $structure['ns_unittests']['types'];
		$this->assertInstanceOf(TableStructure::class, $t);

		$q = new InsertQuery($t);

		$builderFlags = array (
				'no_default' => 0,
				'default_values' => K::BUILDER_INSERT_DEFAULT_VALUES,
				'default_keyword' => K::BUILDER_INSERT_DEFAULT_KEYWORD
		);

		foreach ($builderFlags as $key => $flags)
		{
			$builder = new Reference\StatementBuilder($flags);
			$context = new StatementContext($builder);
			$context->setPivot($t);
			$sql = $q->buildExpression($context);

			$this->derivedFileManager->assertDerivedFile($sql, __METHOD__, $key, 'sql');
		}
	}

	public function testInsertCompanyTask()
	{
		$structure = $this->datasources->get('Company');
		$tableStructure = $structure['ns_unittests']['Tasks'];
		$this->assertInstanceOf(TableStructure::class, $tableStructure);
		$builder = new Reference\StatementBuilder();
		$context = new StatementContext($builder);

		$tests = array (
			'empty' => array (),
			'literals' => [
				'name' => [ 
					X::literal('Test task'), 
					null 
				],
				'creationDateTime' => [
					X::literal(\DateTime::createFromFormat(\DateTime::ISO8601, '2012-01-16T16:35:26+0100')),
					null
				]
			],
			'polish' => [
				'name' => [ 
					X::literal('Random priority'), 
					null
				],
				'priority' => [
					['rand()' => [1, 10]], 
					null
				]
			], 
			'expression' => [
				'creator' => [1 , true],
				'name' => [ "substr (
					'Lorem ipsum', 0, 5)", 
					true
				]
			]
		);

		foreach ($tests as $key => $values)
		{
			$q = new InsertQuery($tableStructure);
			$context->setPivot($tableStructure);

			foreach ($values as $column => $value)
			{
				if ($value instanceof Expression)
					$q[$column] = $value;
				else
					$q->set($column, $value[0], $value[1]);
			}

			$sql = $q->buildExpression($context);

			$this->derivedFileManager->assertDerivedFile($sql, __METHOD__, $key, 'sql');
		}
	}

	/**
	 * @var DatasourceManager
	 */
	private $datasources;

	/**
	 * @var DerivedFileManager
	 */
	private $derivedFileManager;
}