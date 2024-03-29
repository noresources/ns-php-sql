<?php
/**
 * Copyright © 2020 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL;

use NoreSources\Container\Container;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Structure\ColumnStructure;
use NoreSources\SQL\Structure\DatasourceStructure;
use NoreSources\SQL\Structure\IndexStructure;
use NoreSources\SQL\Structure\NamespaceStructure;
use NoreSources\SQL\Structure\StructureElementInterface;
use NoreSources\SQL\Structure\StructureSerializerFactory;
use NoreSources\SQL\Structure\TableStructure;
use NoreSources\SQL\Structure\Inspector\StructureInspector;
use NoreSources\Test\DerivedFileTestTrait;
use PHPUnit\Framework\TestCase;

final class StructureSerializerTest extends TestCase
{

	use DerivedFileTestTrait;

	public function __construct($name = null, array $data = [],
		$dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->initializeDerivedFileTest(__DIR__);
	}

	public function testXMLSerializer()
	{
		/**
		 *
		 * @var StructureSerializerFactory $serializer
		 */
		$serializer = StructureSerializerFactory::getInstance();
		$structure = $serializer->structureFromFile(
			$this->getStructureFile('Company'));

		$this->assertInstanceOf(StructureElementInterface::class,
			$structure, 'Load company structure');

		/**
		 *
		 * @var TableStructure $employees
		 */
		$employees = $structure['ns_unittests']['Employees'];

		$this->assertInstanceOf(TableStructure::class, $employees);

		$constraints = $employees->getConstraints();
		$indexes = $employees->getChildElements(IndexStructure::class);

		$this->assertCount(1, $constraints,
			'Employees table constraints');

		$this->assertCount(1, $indexes);

		foreach ($indexes as $index)
		{
			if ($index->getName() == 'index_employees_name')
				$nameIndex = $index;
		}

		$this->assertInstanceOf(IndexStructure::class, $nameIndex,
			'index_employees_name');
		/**
		 *
		 * @var ColumnStructure $gender
		 */
		$gender = $employees->getColumns()->get('gender');

		$this->assertTrue($gender->has(K::COLUMN_LENGTH),
			'Gender column has length');

		$genderStringLength = Container::keyValue($gender,
			K::COLUMN_LENGTH, false);

		$this->assertEquals(1, $genderStringLength,
			'Gender column length');

		$derivedFilePath = $this->getDerivedFilename(__METHOD__,
			'Company', 'xml');
		$serializer->structureToFile($structure, $derivedFilePath);

		$cloneDerivedFilePath = $this->getDerivedFilename(__METHOD__,
			'CompanyClone', 'xml');
		$structureClone = clone $structure;
		$serializer->structureToFile($structureClone,
			$cloneDerivedFilePath);

		$this->assertFileEquals($derivedFilePath, $cloneDerivedFilePath,
			'Structure clone');

		/**
		 *
		 * @var NamespaceStructure $ns
		 */
		$ns = $structure['ns_unittests'];
		$this->assertInstanceOf(NamespaceStructure::class, $ns);

		$children = $ns->getChildElements();
		$unsortedKeys = Container::keys($children);
		uasort($children,
			[
				StructureInspector::getInstance(),
				'dependencyCompare'
			]);
		$expectedKeys = [
			'Employees',
			'Hierarchy',
			'Tasks',
			'types'
		];
		$sortedKeys = \array_keys($children);

		$this->assertEquals($expectedKeys, $sortedKeys,
			'Elements reordering by dependency');
	}

	public function testBinarySerialize()
	{
		$serializer = StructureSerializerFactory::getInstance();
		$structure = $serializer->structureFromFile(
			$this->getStructureFile('types'));
		$this->assertInstanceOf(DatasourceStructure::class, $structure);

		$serialized = \serialize($structure);
		$unserialized = \unserialize($serialized);
		$this->assertInstanceOf(DatasourceStructure::class,
			$unserialized);
	}

	private function getStructureFile($name)
	{
		return realpath(__DIR__ . '/data/structures/' . $name . '.xml');
	}

	private function getStructureFileContent($name)
	{
		return file_get_contents(
			__DIR__ . '/data/structures/' . $name . '.xml');
	}

	private $dataPath;
}