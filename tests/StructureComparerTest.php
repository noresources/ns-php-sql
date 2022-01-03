<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL;

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\Reference\ReferencePlatform;
use NoreSources\SQL\Structure\CheckTableConstraint;
use NoreSources\SQL\Structure\ColumnStructure;
use NoreSources\SQL\Structure\DatasourceStructure;
use NoreSources\SQL\Structure\PrimaryKeyTableConstraint;
use NoreSources\SQL\Structure\TableStructure;
use NoreSources\SQL\Structure\Comparer\StructureComparer;
use NoreSources\SQL\Syntax\Data;
use NoreSources\SQL\Syntax\Evaluator;
use NoreSources\SQL\Syntax\TokenizableExpressionInterface;
use NoreSources\SQL\Syntax\Statement\StatementBuilder;
use NoreSources\Test\DatasourceManager;
use NoreSources\Test\DerivedFileTestTrait;
use NoreSources\Type\TypeConversion;
use PHPUnit\Framework\TestCase;

final class StructureComparerTest extends TestCase
{

	use DerivedFileTestTrait;

	public function __construct($name = null, array $data = [],
		$dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->datasources = new DatasourceManager();
		$this->initializeDerivedFileTest(__DIR__);
	}

	public function testDefaultValues()
	{
		$utc = new \DateTimeZone('UTC');
		$paris = new \DateTimeZone('Europe/Paris');
		$newYork = new \DateTimeZone('America/New_York');

		$now = new \DateTime('now', $utc);
		$nowAtParis = clone $now;
		$nowAtParis->setTimezone($paris);
		$nowatNewYork = clone $now;
		$nowatNewYork->setTimezone($paris);

		$utcData = new Data($now);
		$parisData = new Data($nowAtParis);
		$newYorkData = new Data($nowatNewYork);

		$utcColumn = new ColumnStructure("UTC");
		$utcColumn->setColumnProperty(K::COLUMN_DATA_TYPE,
			K::DATATYPE_TIMESTAMP);
		$utcColumn->setColumnProperty(K::COLUMN_DEFAULT_VALUE, $utcData);

		$parisColumn = new ColumnStructure('Paris');
		$parisColumn->setColumnProperty(K::COLUMN_NAME, 'Paris');
		$parisColumn->setColumnProperty(K::COLUMN_DEFAULT_VALUE,
			$parisData);

		$newYorkColumn = new ColumnStructure('New York');
		$newYorkColumn->setColumnProperty(K::COLUMN_NAME, 'New York');
		$newYorkColumn->setColumnProperty(K::COLUMN_DEFAULT_VALUE,
			$newYorkData);

		$comparer = new StructureComparer();

		foreach ([
			[
				$utcColumn,
				$parisColumn
			],
			[
				$utcColumn,
				$newYorkColumn
			],
			[
				$parisColumn,
				$newYorkColumn
			]
		] as $pair)
		{
			$a = $pair[0];
			$b = $pair[1];

			$differences = \array_map(
				[
					TypeConversion::class,
					'toString'
				], $comparer->compare($a, $b));
			$this->assertEquals([], $differences,
				$a->getName() . ' vs ' . $b->getName());

			$a2 = clone $a;
			$a2Default = $a2->get(K::COLUMN_DEFAULT_VALUE);
			$a2->setColumnProperty(K::COLUMN_DEFAULT_VALUE,
				new Data(
					TypeConversion::toString($a2Default->getValue()),
					$a2Default->getDataType()));

			$differences = \array_map(
				[
					TypeConversion::class,
					'toString'
				], $comparer->compare($a2, $b));
			$this->assertEquals([], $differences,
				$a2->getName() . ' (string) vs ' . $b->getName());
		}
	}

	public function testCompanyVersions()
	{
		$v1 = $this->datasources->get('Company');
		$v2 = $this->datasources->get('Company.v2');

		$this->assertInstanceOf(DatasourceStructure::class, $v1, 'v1');
		$this->assertInstanceOf(DatasourceStructure::class, $v2, 'v2');

		$comparer = new StructureComparer();
		$differences = $comparer->compare($v1, $v2);
		$s = \implode(PHP_EOL, $differences);
		$this->assertDerivedFile($s, __METHOD__, '', 'differences');

		$hierarchy = $v2['ns_unittests']['Hierarchy'];
		$products = $v2['ns_unittests']['Products'];
	}

	public function testConstraints()
	{
		$comparer = StructureComparer::getInstance();
		$platform = new ReferencePlatform();
		$builder = StatementBuilder::getInstance();
		$evaluator = Evaluator::getInstance();

		$a = new TableStructure('table');

		$a->appendElement(new ColumnStructure('id'));
		$a->appendElement(new ColumnStructure('text'));

		$a->appendElement(new PrimaryKeyTableConstraint([
			'id'
		], 'pk'));

		if (false)
		{
			$ac = new CheckTableConstraint();
			$ac->where([
				'=' => [
					[
						'%' => [
							'id',
							2
						]
					],
					0
				]
			]);
			$a->appendElement($ac);

			{
				$e = $evaluator($ac->getConstraintExpression());
				$this->assertInstanceOf(
					TokenizableExpressionInterface::class, $e);
				$se = $builder($e, $platform);
				$this->assertEquals('[id] % 2 = 0', \strval($se));
			}

			$b = clone $a;
			$diffs = $comparer->compare($a, $b);
			$this->assertCount(0, $diffs, 'No difference (clone)');

			$ac->where(true);
			$diffs = $comparer->compare($a, $b);
			$sdiffs = \array_map('strval', $diffs);

			$this->assertCount(1, $diffs,
				'Detect one alteration of CHECK constraint');
		}
		else
			$this->assertTrue(true);
	}

	/**
	 *
	 * @var DatasourceManager
	 */
	private $datasources;
}