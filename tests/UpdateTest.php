<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL;

use NoreSources\SQL\DBMS\Reference\ReferencePlatform;
use NoreSources\SQL\Syntax\Statement\StatementBuilder;
use NoreSources\SQL\Syntax\Statement\StatementTokenStreamContext;
use NoreSources\SQL\Syntax\Statement\Manipulation\UpdateQuery;
use NoreSources\SQL\Syntax\Statement\Query\SelectQuery;
use NoreSources\Test\ConnectionHelper;
use NoreSources\Test\DatasourceManager;
use NoreSources\Test\DerivedFileTestTrait;
use NoreSources\Test\SqlFormatter;
use PHPUnit\Framework\TestCase;

final class UpdateTest extends TestCase
{
	use DerivedFileTestTrait;

	public function __construct($name = null, array $data = [],
		$dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->initializeDerivedFileTest(__DIR__);
		$this->datasources = new DatasourceManager();
	}

	public function testUpdateBasic()
	{
		$structure = $this->datasources->get('types');
		$tableStructure = $structure['ns_unittests']['types'];
		$this->assertInstanceOf(Structure\TableStructure::class,
			$tableStructure, 'TableStructure instance');

		$connection = ConnectionHelper::createConnection('Reference');

		$platform = $connection->getPlatform();

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
				'fixed_precision' => [
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
				'fixed_precision' => [
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
				],
				'fixed_precision' => [
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
				$q->setColumnData($column, $value[0], $value[1]);
			}

			$result =  StatementBuilder::getInstance()($q, $platform,  $tableStructure);

			$sql = SqlFormatter::format(strval($result), false);
			$this->assertDerivedFile($sql, __METHOD__, $set, 'sql');
		}
	}

	public function testUpdateCompanyEmployees()
	{
		$structure = $this->datasources->get('Company');
		$tableStructure = $structure['ns_unittests']['Employees'];
		$this->assertInstanceOf(Structure\TableStructure::class,
			$tableStructure, 'TableStructure instance');
		$platform = new ReferencePlatform();

		$q = new UpdateQuery($tableStructure);

		$q->setColumnData('salary', 'salary * 2', true);

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

		$result =  StatementBuilder::getInstance() ($q, $platform, $tableStructure);

		$sql = SqlFormatter::format(\strval($result), false);

		$this->assertDerivedFile($sql, __METHOD__, null, 'sql');
	}

	public function testUpdateCompanyEmployees2()
	{
		$structure = $this->datasources->get('Company');
		$tableStructure = $structure['ns_unittests']['Employees'];
		$this->assertInstanceOf(Structure\TableStructure::class,
			$tableStructure, 'TableStructure instance');

		$platform = new ReferencePlatform();
		$context = new StatementTokenStreamContext($platform);
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

		StatementBuilder::getInstance(); // IDO workaround
		$result =  StatementBuilder::getInstance()($q, $platform, $tableStructure);

		$sql = SqlFormatter::format(\strval($result), false);

		$this->assertDerivedFile($sql, __METHOD__, null, 'sql');
	}

	/**
	 *
	 * @var DatasourceManager
	 */
	private $datasources;
}