<?php
namespace NoreSources\SQL;

use NoreSources\SQL\Structure\ColumnStructure;

final class AssetMapTest extends \PHPUnit\Framework\TestCase
{

	public function testIndexed()
	{
		$map = new IndexedAssetMap(
			[
				'foo',
				'bob',
				'value',
				new ColumnStructure('column')
			]);

		$this->assertArrayHasKey(3, $map, 'Has index');

		$this->assertArrayHasKey('Column', $map, 'Has name');
	}

	public function testKeyed()
	{
		$map = new KeyedAssetMap();

		$map['foo'] = 'bar';
		$map['Alice'] = 'bob';
		$map['KEY'] = 'value';

		$this->assertEquals('bar', $map->get('foo'), 'keyed get');
		$this->assertEquals('bob', $map->get('alIce'),
			'keyed get (case)');

		$map->offsetUnset('FOO');
		$this->assertFalse($map->has('foo'), 'Key removed');

		$this->assertTrue($map->has(0), 'Has index (0)');
		$this->assertTrue($map->has(1), 'Has index (1)');
		$this->assertEquals('value', $map[1], 'Index value');
	}
}