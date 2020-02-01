<?php
namespace NoreSources\SQL;

use NoreSources\SQL\Structure\DatasourceStructure;
use NoreSources\SQL\Structure\JSONStructureSerializer;
use NoreSources\SQL\Structure\XMLStructureSerializer;
use NoreSources\Test\DerivedFileManager;

final class StructureSerializerTest extends \PHPUnit\Framework\TestCase
{

	public function __construct($name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->derivedFileManager = new DerivedFileManager(__DIR__);
	}

	public function testLoadTasks()
	{
		$serializer = new XMLStructureSerializer();
		$serializer->unserialize($this->getStructureFileContent('types'));
		$this->assertInstanceOf(DatasourceStructure::class, $serializer->structureElement);

		$j = new JSONStructureSerializer($serializer->structureElement, JSON_PRETTY_PRINT);
		$text = $j->serialize();
		$this->assertEquals('string', gettype($text));
		$a = json_decode($text, true);
		$this->assertArrayHasKey('tablesets', $a);

		$this->derivedFileManager->assertDerivedFile($text, __METHOD__, null, 'json');
	}

	public function testBinarySerialize()
	{
		$serializer = new XMLStructureSerializer();
		$serializer->unserialize($this->getStructureFileContent('types'));
		$this->assertInstanceOf(DatasourceStructure::class, $serializer->structureElement);

		$serialized = \serialize($serializer->structureElement);
		$unserialized = \unserialize($serialized);
		$this->assertInstanceOf(DatasourceStructure::class, $unserialized);
	}

	private function getStructureFileContent($name)
	{
		return file_get_contents(__DIR__ . '/data/structures/' . $name . '.xml');
	}

	private $dataPath;

	/**
	 *
	 * @var DerivedFileManager
	 */
	private $derivedFileManager;
}