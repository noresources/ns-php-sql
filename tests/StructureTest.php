<?php
namespace NoreSources\SQL;

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Structure\ColumnStructure;
use NoreSources\SQL\Structure\DatasourceStructure;
use NoreSources\SQL\Structure\NamespaceStructure;
use NoreSources\SQL\Structure\Identifier;
use NoreSources\SQL\Structure\TableStructure;

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
			$this->assertInstanceOf(Identifier::class,
				$test, $label);
			$this->assertEquals($asString, $test->getPath(), $label);
			$this->assertEquals($asArray, $test->getPathParts(), $label);
		}
	}
}