<?php
namespace NoreSources\SQL;

use NoreSources\SQL\DBMS\ConnectionHelper;
use NoreSources\SQL\DBMS\Reference\ReferenceStatementBuilder;
use NoreSources\SQL\Expression\TokenStream;
use NoreSources\SQL\Statement\StatementTokenStreamContext;
use NoreSources\SQL\Statement\Manipulation\UpdateQuery;
use NoreSources\SQL\Statement\Query\SelectQuery;
use NoreSources\Test\DatasourceManager;
use NoreSources\Test\DerivedFileManager;

final class UpdateTest extends \PHPUnit\Framework\TestCase
{

	public function __construct($name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->derivedFileManager = new DerivedFileManager(__DIR__);
		$this->datasources = new DatasourceManager();
	}

	public function testUpdateBasic()
	{
		$structure = $this->datasources->get('types');
		$tableStructure = $structure['ns_unittests']['types'];
		$this->assertInstanceOf(Structure\TableStructure::class, $tableStructure,
			'TableStructure instance');

		$connection = ConnectionHelper::createConnection('Reference');

		$builder = $connection->getStatementBuilder();
		$context = new StatementTokenStreamContext($builder);
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
			$q = new UpdateQuery('types', 't');

			foreach ($columnValues as $column => $value)
			{
				$q->setColumnValue($column, $value[0], $value[1]);
			}

			$stream = new TokenStream();
			$q->tokenize($stream, $context);
			$result = $builder->finalizeStatement($stream, $context);
			$sql = \SqlFormatter::format(strval($result), false);
			$this->derivedFileManager->assertDerivedFile($sql, __METHOD__, $set, 'sql');
		}
	}

	public function testUpdateCompanyEmployees()
	{
		$structure = $this->datasources->get('Company');
		$tableStructure = $structure['ns_unittests']['Employees'];
		$this->assertInstanceOf(Structure\TableStructure::class, $tableStructure,
			'TableStructure instance');
		$builder = new ReferenceStatementBuilder();
		$context = new StatementTokenStreamContext($builder);
		$context->setPivot($tableStructure);

		$q = new UpdateQuery($tableStructure);

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
		$result = $builder->finalizeStatement($stream, $context);
		$sql = \SqlFormatter::format(\strval($result), false);

		$this->derivedFileManager->assertDerivedFile($sql, __METHOD__, null, 'sql');
	}

	public function testUpdateCompanyEmployees2()
	{
		$structure = $this->datasources->get('Company');
		$tableStructure = $structure['ns_unittests']['Employees'];
		$this->assertInstanceOf(Structure\TableStructure::class, $tableStructure,
			'TableStructure instance');
		$builder = new ReferenceStatementBuilder();
		$context = new StatementTokenStreamContext($builder);
		$context->setPivot($tableStructure);

		$q = new UpdateQuery($tableStructure);

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
		$result = $builder->finalizeStatement($stream, $context);
		$sql = \SqlFormatter::format(\strval($result), false);

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