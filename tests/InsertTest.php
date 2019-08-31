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
		$structure = $this->datasources->get ('types');
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
			$builder = new GenericStatementBuilder($flags);
			$context = new StatementContext($builder);
			$sql = $q->buildExpression($context);

			$this->derivedFileManager->assertDerivedFile($sql, __METHOD__, $key, 'sql');
		}
	}

	public function testInsertCompanyTask()
	{
		$structure = $this->datasources->get('Company');
		$tableStructure = $structure['ns_unittests']['Tasks'];
		$this->assertInstanceOf(TableStructure::class, $tableStructure);
		$builder = new GenericStatementBuilder();
		$context = new StatementContext($builder);

		$tests = array (
				'empty' => array (),
				'literals' => array (
						'name' => X::literal('Test task'),
						'creationDateTime' => X::literal(\DateTime::createFromFormat(\DateTime::ISO8601, '2012-01-16T16:35:26+0100'))
				),
				'polish' => [
						'name' => X::literal('Random priority'),
						'priority' => ['rand()' => [1, 10]]
				], 'expression' => [
					'creator' => 1,
					'name' => "substr ('Lorem ipsum', 0, 5)"
				]
		);

		foreach ($tests as $key => $values)
		{
			$q = new InsertQuery($tableStructure);

			foreach ($values as $column => $value)
			{
				if ($value instanceof Expression)
					$q[$column] = $value;
				else
					$q->set($column, $value);
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
	 * 
	 * @var DerivedFileManager
	 */
	private $derivedFileManager;
}