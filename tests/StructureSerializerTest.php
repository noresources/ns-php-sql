<?php
namespace NoreSources\SQL;

use NoreSources\SQL\Structure\DatasourceStructure;
use NoreSources\SQL\Structure\StructureSerializerFactory;
use NoreSources\Test\DerivedFileManager;

final class StructureSerializerTest extends \PHPUnit\Framework\TestCase
{

	public function __construct($name = null, array $data = [],
		$dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->derivedFileManager = new DerivedFileManager(__DIR__);
	}

	public function testBinarySerialize()
	{
		$structure = StructureSerializerFactory::structureFromFile(
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