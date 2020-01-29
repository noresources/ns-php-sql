<?php
namespace NoreSources\Test;

use NoreSources\SQL\Structure\DatasourceStructure;
use NoreSources\SQL\Structure\StructureSerializerFactory;

class DatasourceManager extends \PHPUnit\Framework\TestCase
{

	public function __construct($name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->datasources = new \ArrayObject();
		$this->basePath = __DIR__ . '/..';
	}

	public function get($name)
	{
		if ($this->datasources->offsetExists($name))
			return $this->datasources[$name];

		$filename = $this->basePath . '/data/structures/' . $name . '.xml';

		$this->assertFileExists($filename, $name . ' datasource loading');

		$structure = StructureSerializerFactory::structureFromFile($filename);

		$this->assertInstanceOf(DatasourceStructure::class, $structure,
			$name . ' datasource loading');

		$this->datasources->offsetSet($name, $structure);

		return $structure;
	}

	/**
	 *
	 * @var \ArrayObject
	 */
	private $datasources;

	private $basePath;
}
