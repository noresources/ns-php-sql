<?php

namespace NoreSources\SQL;

use PHPUnit\Framework\TestCase;

require_once (__DIR__ . '/../autoload.php');

class DatasourceManager extends TestCase
{

	public function __construct()
	{
		$this->datasources = new \ArrayObject();
	}

	public function get($name)
	{
		if ($this->datasources->offsetExists($name))
			return $this->datasources[$name];

		$filename = __DIR__ . '/data/structures/' . $name . '.xml';

		$this->assertFileExists($filename, $name . ' datasource loading');

		$content = file_get_contents($filename);
		$serializer = new XMLStructureSerializer();
		$serializer->unserialize($filename);
		
		$this->assertInstanceOf(DatasourceStructure::class, 
				$serializer->structureElement,
				$name . ' datasource loading'
		);
		
		$this->datasources->offsetSet($name, $serializer->structureElement);
		
		return $serializer->structureElement;
	}

	/**
	 * @var \ArrayObject
	 */
	private $datasources;
}

class DerivedFileManager extends TestCase
{
	const DIRECTORY_REFERENCE = 'reference';
	const DIRECTORY_DERIVED = 'derived';

	public function __construct()
	{
		$this->derivedDataFiles = new \ArrayObject();
	}

	public function __destruct()
	{
		if (count($this->derivedDataFiles))
		{
			foreach ($this->derivedDataFiles as $path)
			{
				unlink($path);
			}

			@rmdir(__DIR__ . '/' . self::DIRECTORY_DERIVED);
		}
	}

	/**
	 * Save derived file, compare to reference
	 *
	 * @param unknown $data
	 * @param unknown $suffix
	 * @param unknown $extension
	 */
	public function assertDerivedFile($data, $method, $suffix, $extension, $label = '')
	{
		$reference = $this->buildFilename(self::DIRECTORY_REFERENCE, $method, $suffix, $extension);
		$derived = $this->buildFilename(self::DIRECTORY_DERIVED, $method, $suffix, $extension);
		$label = (strlen($label) ? ($label . ': ') : '');

		$result = $this->createDirectoryPath($derived);

		if ($result)
		{
			$result = file_put_contents($derived, $data);
			$this->assertNotFalse($result, $label . 'Write derived data');
			$this->assertFileExists($derived, $label . 'Derived file exists');

			if ($result)
			{
				$this->derivedDataFiles[] = $derived;
			}
		}

		if (\is_file($reference))
		{
			$this->assertFileEquals($reference, $derived, $label . 'Compare with reference');
		}
		else
		{
			$result = $this->createDirectoryPath($reference);

			if ($result)
			{
				$result = file_put_contents($reference, $data);
				$this->assertNotFalse($result, $label . 'Write reference data to ' . $reference);
				$this->assertFileExists($reference, $label . 'Reference file exists');
			}
		}
	}

	private function buildFilename($directory, $method, $suffix, $extension)
	{
		preg_match('/.*\\\\(.*?)Test::test(.*)$/', $method, $m);
		$cls = $m[1];
		$method = str_replace($cls, '', $m[2]);

		if (\is_string($suffix) && strlen($suffix))
			$method .= '_' . $suffix;
		return __DIR__ . '/' . $directory . '/' . $cls . '_' . $method . '.' . $extension;
	}

	private function createDirectoryPath($filepath)
	{
		$path = dirname($filepath);
		$result = true;
		if (!is_dir($path))
			$result = @mkdir($path, 0777, true);
		$this->assertTrue($result, 'Create directory ' . $path);
		return $result;
	}

	/**
	 * @var array
	 */
	private $derivedDataFiles;
}