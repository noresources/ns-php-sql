<?php
namespace NoreSources\SQL;

use NoreSources\SQL\DBMS\Reference\ReferencePlatform;
use NoreSources\SQL\Structure\CheckTableConstraint;
use NoreSources\SQL\Structure\ColumnStructure;
use NoreSources\SQL\Structure\DatasourceStructure;
use NoreSources\SQL\Structure\PrimaryKeyTableConstraint;
use NoreSources\SQL\Structure\TableStructure;
use NoreSources\SQL\Structure\Comparer\StructureComparer;
use NoreSources\SQL\Syntax\Evaluator;
use NoreSources\SQL\Syntax\TokenizableExpressionInterface;
use NoreSources\SQL\Syntax\Statement\StatementBuilder;
use NoreSources\Test\DatasourceManager;
use NoreSources\Test\DerivedFileManager;
use PHPUnit\Framework\TestCase;

final class StructureComparerTest extends TestCase
{

	public function __construct($name = null, array $data = [],
		$dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->datasources = new DatasourceManager();
		$this->derivedFileManager = new DerivedFileManager(__DIR__);
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
		$this->derivedFileManager->assertDerivedFile($s, __METHOD__, '',
			'differences');

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

	/**
	 * /**
	 *
	 * @var DerivedFileManager
	 */
	private $derivedFileManager;
}