<?php
namespace NoreSources\SQL;

use NoreSources\Container;
use NoreSources\SQL\Constants as K;

final class TextDataTypeDescription extends \PHPUnit\Framework\TestCase
{

	public function testAfinity()
	{
		$d = DataTypeDescription::getInstance();

		$tests = [
			'nada' => [
				'a' => 0,
				'b' => 0,
				'affinities' => [],
				'flags' => DataTypeDescription::AFFINITY_MATCH_STRICT |
				DataTypeDescription::AFFINITY_MATCH_NULL
			],
			'time types' => [
				'a' => K::DATATYPE_DATETIME,
				'b' => K::DATATYPE_TIMESTAMP,
				'affinities' => [
					K::DATATYPE_TIMESTAMP
				],
				'flags' => DataTypeDescription::AFFINITY_MATCH_ALL
			]
		];

		foreach ($tests as $label => $test)
		{
			$ta = $test['a'];
			$tb = $test['b'];

			$expectedFlags = $test['flags'];

			$a = $d->getAffinities($ta);
			sort($a);
			$b = $d->getAffinities($tb);
			sort($b);

			if (Container::keyExists($test, 'affinities'))
			{
				$expected = $test['affinities'];
				sort($expected);
				$this->assertEquals($expected, $a,
					$label . ' ' . self::name($ta) . ' affinites');
				$this->assertEquals($expected, $b,
					$label . ' ' . self::name($tb) . ' affinites');
			}

			$flags = $d->compareAffinity($ta, $tb);
			$this->assertEquals($expectedFlags, $flags,
				$label . ' ' . 'Affinity comparison');
		}
	}

	public static function name($dataType)
	{
		$names = DataTypeDescription::getInstance()->getNames($dataType);
		return \implode(':', $names);
	}
}
