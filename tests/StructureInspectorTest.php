<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL;

use NoreSources\Container\Container;
use NoreSources\SQL\Structure\Inspector\StructureInspector;
use NoreSources\Test\DatasourceManagerTrait;
use PHPUnit\Framework\TestCase;

final class StructureInspectorTest extends TestCase
{

	use DatasourceManagerTrait;

	const TEST_NAMESPACE = 'ns_unittests';

	public function __construct($name = null, array $data = [],
		$dataName = '')
	{
		parent::__construct($name, $data, $dataName);

		$this->initializeDatasourceManager(__DIR__);
	}

	public function testDependency()
	{
		$structure = $this->getDatasource('Company');

		$inspector = StructureInspector::getInstance();
		$list = $inspector->getReverseReferenceMap($structure);

		$employees = $structure[self::TEST_NAMESPACE]['Employees'];
		$employeesId = $employees->getIdentifier()->getPath();

		$this->assertArrayHasKey($employeesId, $list,
			'Employees reverse dependency');

		foreach ([
			$structure[self::TEST_NAMESPACE]['Tasks'],
			$structure[self::TEST_NAMESPACE]['Tasks']['fk_creator'],
			$structure[self::TEST_NAMESPACE]['Hierarchy'],
			$structure[self::TEST_NAMESPACE]['Hierarchy']['hierarchy_managerId_foreignkey']
		] as $reference)
		{
			$this->assertContains($reference, $list[$employeesId],
				$reference->getIdentifier()
					->getPath() . ' depends on ' . $employeesId);
		}

		if (false)
			echo (PHP_EOL .
				Container::implode($list, PHP_EOL,
					function ($path, $a) {
						$s = $path . ' = [' . PHP_EOL . "\t";
						$s .= Container::implodeValues($a,
							',' . PHP_EOL . "\t",
							function ($e) {
								$p = $e->getIdentifier()->getPath();
								if (!empty($p))
									return $p;
								$p = '';
								if ($e->getParentElement())
									$p = $e->getParentElement()
										->getIdentifier()
										->getPath();
								return $p . '.' . $e->getElementKey();
							});
						$s .= PHP_EOL . ']' . PHP_EOL;
						return $s;
					}));
	}
}