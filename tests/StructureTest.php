<?php
namespace NoreSources\SQL;

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Structure\ColumnStructure;
use NoreSources\SQL\Structure\DatasourceStructure;
use NoreSources\SQL\Structure\ForeignKeyTableConstraint;
use NoreSources\SQL\Structure\Identifier;
use NoreSources\SQL\Structure\IndexStructure;
use NoreSources\SQL\Structure\NamespaceStructure;
use NoreSources\SQL\Structure\PrimaryKeyTableConstraint;
use NoreSources\SQL\Structure\Structure;
use NoreSources\SQL\Structure\TableStructure;
use NoreSources\SQL\Structure\UniqueTableConstraint;

final class StructureTest extends \PHPUnit\Framework\TestCase
{

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

		$this->assertTrue(Structure::dependsOn($index, $table),
			'Index depends on parent table');
		$this->assertTrue(Structure::hasData($table), 'Table has data');
		$this->assertFalse(Structure::hasData($index),
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

		$col_aName = Structure::makeIdentifier($col_a);
		$col_aKey = Structure::makeCanonicalKey($col_a);

		$pkName = Structure::makeIdentifier($pk);
		$pkKey = Structure::makeCanonicalKey($pk);

		$this->assertEquals($col_aName, $col_aKey,
			'Fully qualified element has key = identifier');

		$this->assertNotEquals($pkName, $pkKey,
			'Non fully qualified element has key != identifier');

		$this->assertEquals($ns,
			Structure::commonAncestor($table, $otherTable),
			'Common ancestor of tables is the namespace');

		$this->assertEquals($ns,
			Structure::commonAncestor($table, $col_a_ref),
			'Common ancestor of table and a column is the namespace');

		$expectedTree = [
			$source,
			$ns,
			$table
		];
		$tree = Structure::ancestorTree($pk);

		$this->assertCount(\count($expectedTree), $tree,
			'Primary key ancestor tree element count');

		$this->assertEquals($expectedTree, $tree,
			'Primary key ancestors');

		$this->assertFalse(Structure::dependsOn($col_a, $col_b),
			'Columns of table does not depends on themselve (1)');
		$this->assertFalse(Structure::dependsOn($col_b, $col_a),
			'Columns of table does not depends on themselve (2)');

		$this->assertFalse(Structure::dependsOn($table, $otherTable),
			'table does not depends on otherTable');
		$this->assertTrue(Structure::dependsOn($otherTable, $table),
			'OtherTable depends on table (foreign key)');

		$this->assertFalse(Structure::dependsOn($col_b, $otherTable),
			'Column B does not depends on otherTable');
		$this->assertTrue(Structure::dependsOn($col_a_ref, $table),
			'Column A ref. depends on table (foreign key)');

		$this->assertTrue(Structure::dependsOn($fk_table_a, $table),
			'Foreign key to table depends on table');
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
	}
}