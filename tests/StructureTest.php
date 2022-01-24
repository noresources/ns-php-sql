<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL;

use NoreSources\Container\Container;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Structure\ColumnStructure;
use NoreSources\SQL\Structure\DatasourceStructure;
use NoreSources\SQL\Structure\ForeignKeyTableConstraint;
use NoreSources\SQL\Structure\Identifier;
use NoreSources\SQL\Structure\IndexStructure;
use NoreSources\SQL\Structure\NamespaceStructure;
use NoreSources\SQL\Structure\PrimaryKeyTableConstraint;
use NoreSources\SQL\Structure\StructureElementContainerInterface;
use NoreSources\SQL\Structure\TableStructure;
use NoreSources\SQL\Structure\UniqueTableConstraint;
use NoreSources\SQL\Structure\Inspector\StructureInspector;
use NoreSources\Test\DatasourceManagerTrait;
use PHPUnit\Framework\TestCase;

final class StructureTest extends TestCase
{
	use DatasourceManagerTrait;

	public function testNameProperty()
	{
		$source = new DatasourceStructure();
		$ns = new NamespaceStructure('ns', $source);
		$table = new TableStructure('table', $ns);
		$column = new ColumnStructure('column', $table);

		$nameProperty = $column->get(K::COLUMN_NAME);
		$structureName = $column->getName();
		$this->assertEquals($structureName, $nameProperty,
			'Structure name and COLUMN_NAME property');
	}

	public function testCanonicalKey()
	{
		$source = new DatasourceStructure();
		$ns = new NamespaceStructure('ns');
		$source->appendElement($ns);
		$table = new TableStructure('table');
		$ns->appendElement($table);
		$table->appendElement($col_a = new ColumnStructure('a'));
		$table->appendElement($col_b = new ColumnStructure('b'));
		$table->appendElement($col_c = new ColumnStructure('c'));
		$table->appendElement(
			$pk = new PrimaryKeyTableConstraint([
				'a'
			]));
		$table->appendElement(
			$u = new UniqueTableConstraint([
				'b'
			], 'u'));

		$table->appendElement($index = new IndexStructure('index'));
		$index->columns('c');

		$this->assertTrue(
			StructureInspector::getInstance()->dependsOn($index, $table),
			'Index depends on parent table');
		$this->assertTrue(
			StructureInspector::getInstance()->hasData($table),
			'Table has data');
		$this->assertFalse(
			StructureInspector::getInstance()->hasData($index),
			'Index does not have data');

		$otherTable = new TableStructure('other');
		$otherTable->appendElement($c = new ColumnStructure('c'));
		$otherTable->appendElement($d = new ColumnStructure('d'));
		$otherTable->appendElement(
			$col_a_ref = new ColumnStructure('a_ref'));
		$otherTable->appendElement(
			$fk_table_a = new ForeignKeyTableConstraint($table,
				[
					'a_ref' => 'a'
				]));
		$ns->appendElement($otherTable);

		$col_aName = Identifier::make($col_a);
		$col_aKey = Identifier::make($col_a, false);

		$pkName = Identifier::make($pk);
		$pkKey = Identifier::make($pk, false);

		$this->assertEquals($col_aName, $col_aKey,
			'Fully qualified element has key = identifier');

		$this->assertNotEquals($pkName, $pkKey,
			'Non fully qualified element has key != identifier');

		$this->assertEquals($ns,
			StructureInspector::getInstance()->getCommonAncestor($table,
				$otherTable),
			'Common ancestor of tables is the namespace');

		$this->assertEquals($ns,
			StructureInspector::getInstance()->getCommonAncestor($table,
				$col_a_ref),
			'Common ancestor of table and a column is the namespace');

		$expectedTree = [
			$source,
			$ns,
			$table
		];
		$tree = StructureInspector::getInstance()->getAncestorTree($pk);

		$this->assertCount(\count($expectedTree), $tree,
			'Primary key ancestor tree element count');

		$this->assertEquals($expectedTree, $tree,
			'Primary key ancestors');

		$this->assertFalse(
			StructureInspector::getInstance()->dependsOn($col_a, $col_b),
			'Columns of table does not depends on themselve (1)');
		$this->assertFalse(
			StructureInspector::getInstance()->dependsOn($col_b, $col_a),
			'Columns of table does not depends on themselve (2)');

		$this->assertFalse(
			StructureInspector::getInstance()->dependsOn($table,
				$otherTable), 'table does not depends on otherTable');
		$this->assertTrue(
			StructureInspector::getInstance()->dependsOn($otherTable,
				$table), 'OtherTable depends on table (foreign key)');

		$this->assertFalse(
			StructureInspector::getInstance()->dependsOn($col_b,
				$otherTable), 'Column B does not depends on otherTable');
		$this->assertTrue(
			StructureInspector::getInstance()->dependsOn($col_a_ref,
				$table), 'Column A ref. depends on table (foreign key)');

		$this->assertTrue(
			StructureInspector::getInstance()->dependsOn($fk_table_a,
				$table), 'Foreign key to table depends on table');
	}

	public function testIdentifier()
	{
		$asString = 'ns.table.column';
		$asArray = [
			'ns',
			'table',
			'column'
		];
		$alreadyOk = new Identifier($asString);

		foreach ([
			'from string' => Identifier::make($asString),
			'from array' => Identifier::make($asArray),
			'already ok' => $alreadyOk
		] as $label => $test)
		{
			$this->assertInstanceOf(Identifier::class, $test, $label);
			$this->assertEquals($asString, $test->getPath(), $label);
			$this->assertEquals($asArray, $test->getPathParts(), $label);
		}

		$a = Identifier::make('foo.bar');
		$b = Identifier::make([
			'foo',
			'bar'
		]);
		$c = Identifier::make([
			'foo',
			'baz'
		]);

		$this->assertEquals(0, $a->compare($b), 'a == b');
		$this->assertEquals(0, $b->compare($a), 'b == a');
		$this->assertNotEquals(0, $a->compare($c), 'a != c');
	}

	public function testDescendant()
	{
		$structure = $this->getDatasource('Company');

		$tests = [
			[
				'identifier' => '',
				'expected' => DatasourceStructure::class
			],
			[
				'identifier' => 'ns_unittests',
				'expected' => NamespaceStructure::class
			],
			[
				'identifier' => 'ns_unittests.Employees',
				'expected' => TableStructure::class
			],
			[
				'identifier' => 'ns_unittests.Employees.name',
				'expected' => ColumnStructure::class
			],
			[
				'identifier' => 'ns_unittests.Employees.pk_id',
				'expected' => PrimaryKeyTableConstraint::class
			],
			[
				'identifier' => 'ns_unittests.Employees.index_employees_name',
				'expected' => IndexStructure::class
			]
		];

		foreach ($tests as $test)
		{
			$identifier = $test['identifier'];
			$element = false;
			if ($structure instanceof StructureElementContainerInterface)
				$element = $structure->findDescendant($identifier);
			if (($expected = Container::keyValue($test, 'expected')))
			{
				$this->assertInstanceOf($expected, $element,
					'Element found');
			}
			else
				$this->assertEquals(null, $element,
					'Element "' . $identifier . '" does not exists');
		}
	}

	public function __construct($name = null, array $data = [],
		$dataName = '')
	{
		parent::__construct($name, $data, $dataName);

		$this->initializeDatasourceManager(__DIR__);
	}
}
