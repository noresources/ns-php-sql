<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL;

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Syntax\Data;
use NoreSources\SQL\Syntax\Keyword;
use NoreSources\Type\TypeConversion;
use NoreSources\Type\TypeDescription;
use PHPUnit\Framework\TestCase;

final class DataDescriptionTest extends TestCase
{

	public function testValue()
	{
		$tests = [
			[
				'input' => new Keyword(K::KEYWORD_NULL),
				'value' => null
			],
			[
				'input' => true,
				'value' => true
			],
			[
				'input' => new Data('42', K::DATATYPE_INTEGER),
				'value' => 42
			]
		];

		$dd = DataDescription::getInstance();
		foreach ($tests as $test)
		{
			$actual = $dd->getValue($test['input']);
			$this->assertEquals($test['value'], $actual,
				TypeDescription::getLocalName($test["input"]) . ' to ' .
				TypeDescription::getLocalName($test['value']));
		}
	}

	public function testSimilar()
	{
		$tests = [
			[
				false,
				0,
				true
			],
			[
				1,
				true,
				true
			],
			[
				42,
				true,
				false
			],
			[
				'42',
				42,
				true
			],
			[
				null,
				'',
				true
			],
			[
				'',
				null,
				true
			],
			[
				'0',
				false,
				true
			]
		];

		$dd = DataDescription::getInstance();

		foreach ($tests as $test)
		{
			$a = $test[0];
			$b = $test[1];
			$at = K::dataTypeName($dd->getDataType($a));
			$bt = K::dataTypeName($dd->getDataType($b));
			$expected = $test[2];
			$label = $at . ' (' . TypeConversion::toString($a) . ') vs ' .
				$bt . ' (' . TypeConversion::toString($b) . ')';

			$actual = $dd->isSimilar($a, $b);
			$this->assertEquals($expected, $actual, $label);
		}
	}
}

