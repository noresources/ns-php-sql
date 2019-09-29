<?php
namespace NoreSources\SQL;

use PHPUnit\Framework\TestCase;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\ExpressionEvaluator as X;

final class UpdateTest extends TestCase
{

	public function __construct()
	{
		parent::__construct();
		$this->derivedFileManager = new DerivedFileManager();
		$this->datasources = new DatasourceManager();
	}

	public function testUpdateBasic()
	{
		$structure = $this->datasources->get('types');
		$tableStructure = $structure['ns_unittests']['types'];
		$this->assertInstanceOf(TableStructure::class, $tableStructure);

		$connection = ConnectionHelper::createConnection('reference');

		$builder = $connection->getStatementBuilder();
		$context = new StatementContext($builder);
		$context->setPivot($tableStructure);
		
		$sets = [
			'literals' => [
					'base' => [ 'abc', false ],
					'binary' => [ '456', false ],
					'boolean' => [false, false],
					'float' => [ 987.789, false ],
					'timestamp' => [0, false]
			],
			'literals 2' => [
					'boolean' => ['1', false],
					'float' => [ '987.789', false ],
					'timestamp' => ['2019-09-07 15:16:17+0300', false]
			],
			'timestamp to pod' => [
					'base' => [ new \DateTime ('2017-12-07 08:10:13.256+0700'), false ],
					'int' => [ new \DateTime ('2017-12-07 08:10:13.256+0700'), false ],
					'float' => [ new \DateTime ('2017-12-07 08:10:13.256+0700'), false ]
			]
		];

		foreach ($sets as $set => $columnValues)
		{
			$q = new UpdateQuery('types', 't');

			foreach ($columnValues as $column => $value)
			{
				$q->set($column, $value[0], $value[1]);
			}

			$stream = new TokenStream();
			$q->tokenize($stream, $context);
			$sql = $builder->buildStatementData($stream);
			$sql = \SqlFormatter::format(strval($sql), false);
			$this->derivedFileManager->assertDerivedFile($sql, __METHOD__, $set, 'sql');
		}
	}

	public function testUpdateCompanyEmployees()
	{
		$structure = $this->datasources->get('Company');
		$tableStructure = $structure['ns_unittests']['Employees'];
		$this->assertInstanceOf(TableStructure::class, $tableStructure);
		$builder = new Reference\StatementBuilder();
		$context = new StatementContext($builder);
		$context->setPivot($tableStructure);
		$q = new UpdateQuery($tableStructure);

		$q->set('salary', 'salary * 2', true);
		$q->where('id=1');

		$stream = new TokenStream();
		$q->tokenize($stream, $context);
		$sql = $builder->buildStatementData($stream);

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