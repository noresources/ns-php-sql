<?php

namespace NoreSources\SQL;

use PHPUnit\Framework\TestCase;

final class StructureSerializerTest extends TestCase
{

	public function __construct()
	{
		parent::__construct();
	}

	public function testLoadTasks()
	{
		$serializer = new XMLStructureSerializer();
		$serializer->unserialize($this->getStructureFileContent('types'));
		$j = new JSONStructureSerializer($serializer->structureElement, JSON_PRETTY_PRINT);
		echo ($j->serialize());
	}

	private function getStructureFileContent($name)
	{
		return file_get_contents(__DIR__ . '/data/structures/' . $name . '.xml');
	}

	private $dataPath;
}