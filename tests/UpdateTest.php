<?php
namespace NoreSources\SQL;

use PHPUnit\Framework\TestCase;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Expression\Evaluator as X;

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
		$context = new BuildContext($builder);
		$context->setPivot($tableStructure);

		$sets = [
			'literals' => [
				'base' => [
					'abc',
					false
				],
				'binary' => [
					'456',
					false
				],
				'boolean' => [
					false,
					false
				],
				'float' => [
					987.789,
					false
				],
				'timestamp' => [
					0,
					false
				]
			],
			'literals 2' => [
				'boolean' => [
					'1',
					false
				],
				'float' => [
					'987.789',
					false
				],
				'timestamp' => [
					'2019-09-07 15:16:17+0300',
					false
				]
			],
			'timestamp to pod' => [
				'base' => [
					new \DateTime('2017-12-07 08:10:13.256+0700'),
					false
				],
				'int' => [
					new \DateTime('2017-12-07 08:10:13.256+0700'),
					false
				],
				'float' => [
					new \DateTime('2017-12-07 08:10:13.256+0700'),
					false
				]
			]
		];

		foreach ($sets as $set => $columnValues)
		{
			$q = new Statement\UpdateQuery('types', 't');

			foreach ($columnValues as $column => $value)
			{
				$q->setColumnValue($column, $value[0], $value[1]);
			}

			$stream = new TokenStream();
			$q->tokenize($stream, $context);
			$sql = $builder->finalize($stream, $context);
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
		$context = new BuildContext($builder);
		$context->setPivot($tableStructure);

		$q = new Statement\UpdateQuery($tableStructure);

		$q->setColumnValue('salary', 'salary * 2', true);

		$sub = new SelectQuery('Employees', 'e');
		$sub->columns('id');
		$sub->where('id > 2');

		$q->where([
			'in' => [
				'id',
				$sub
			]
		], [
			'!in' => [
				'id',
				4,
				5
			]
		]);

		$stream = new TokenStream();
		$q->tokenize($stream, $context);
		$result = $builder->finalize($stream, $context);

		$this->assertEquals($context, $result, 'builder::finalize() result');

		$sql = \SqlFormatter::format($result, false);

		$this->derivedFileManager->assertDerivedFile($sql, __METHOD__, null, 'sql');
	}

	public function testUpdateCompanyEmployees2()
	{
		$structure = $this->datasources->get('Company');
		$tableStructure = $structure['ns_unittests']['Employees'];
		$this->assertInstanceOf(TableStructure::class, $tableStructure);
		$builder = new Reference\StatementBuilder();
		$context = new BuildContext($builder);
		$context->setPivot($tableStructure);

		$q = new Statement\UpdateQuery($tableStructure);

		$q('salary', 'salary + 100');
		$q->where([
			'gender' => "'F'"
		], [
			'<' => [
				'salary',
				1000
			]
		]);

		$stream = new TokenStream();
		$q->tokenize($stream, $context);
		$result = $builder->finalize($stream, $context);

		$this->assertEquals($context, $result, 'builder::finalize() result');

		$sql = \SqlFormatter::format($result, false);

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