<?php
namespace NoreSources\SQL;

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Structure\ColumnStructure;
use NoreSources\SQL\Structure\DatasourceStructure;
use NoreSources\SQL\Structure\NamespaceStructure;
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
}