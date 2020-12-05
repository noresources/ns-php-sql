<?php
/**
 * Copyright © 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL;

use NoreSources\Container;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Structure\ColumnStructure;
use NoreSources\SQL\Structure\DatasourceStructure;
use NoreSources\SQL\Structure\IndexTableConstraint;
use NoreSources\SQL\Structure\StructureElementInterface;
use NoreSources\SQL\Structure\StructureSerializerFactory;
use NoreSources\SQL\Structure\TableStructure;
use NoreSources\Test\DerivedFileManager;

final class StructureSerializerTest extends \PHPUnit\Framework\TestCase
{

	public function __construct($name = null, array $data = [],
		$dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->derivedFileManager = new DerivedFileManager(__DIR__);
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
		$indexConstraint = null;

		$this->assertCount(2, $constraints,
			'Employees table constraints');

		foreach ($constraints as $constraint)
		{
			if ($constraint->getName() == 'index_employees_name')
				$indexConstraint = $constraint;
		}

		$this->assertInstanceOf(IndexTableConstraint::class,
			$indexConstraint, 'index_employees_name');

		/**
		 *
		 * @var ColumnStructure $gender
		 */
		$gender = $employees->getColumn('gender');

		$this->assertTrue($gender->has(K::COLUMN_LENGTH),
			'Gender column has length');

		$genderStringLength = Container::keyValue($gender,
			K::COLUMN_LENGTH, false);

		$this->assertEquals(1, $genderStringLength,
			'Gender column length');

		$derivedFilePath = $this->derivedFileManager->getDerivedFilename(
			__METHOD__, 'Company', 'xml');

		$serializer->structureToFile($structure, $derivedFilePath);
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

	/**
	 *
	 * @var DerivedFileManager
	 */
	private $derivedFileManager;
}