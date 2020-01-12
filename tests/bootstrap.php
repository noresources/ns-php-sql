<?php
namespace NoreSources\SQL;

require_once (__DIR__ . '/../vendor/autoload.php');

use NoreSources\SQL\Structure\DatasourceStructure;
use NoreSources\SQL\Structure\StructureSerializerFactory;

class DatasourceManager extends \PHPUnit\Framework\TestCase
{

	public function __construct($name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->datasources = new \ArrayObject();
	}

	public function get($name)
	{
		if ($this->datasources->offsetExists($name))
			return $this->datasources[$name];

		$filename = __DIR__ . '/data/structures/' . $name . '.xml';

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
}

class DerivedFileManager extends \PHPUnit\Framework\TestCase
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
			foreach ($this->derivedDataFiles as $path => $persistent)
			{
				if ($persistent)
					continue;
				if (file_exists($path))
				{
					unlink($path);
				}
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
	public function assertDerivedFile($data, $method, $suffix, $extension, $label = '', $eol = null)
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
				$this->derivedDataFiles->offsetSet($derived, false);
			}
		}

		if (\is_file($reference))
		{
			$this->derivedDataFiles->offsetSet($derived, true);
			//$this->assertFileEquals($reference, $derived, $label . 'Compare with reference');
			$this->assertEquals($this->loadFile($reference, 'lf'),
				$this->convertEndOfLine($data, 'lf'));
			$this->derivedDataFiles->offsetSet($derived, false);
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

	public function setPersistent($path, $value)
	{
		if ($this->derivedDataFiles->offsetExists($path))
			$this->derivedDataFiles->offsetSet($path, $value);
	}

	public function registerDerivedFile($subDirectory, $method, $suffix, $extension)
	{
		$directory = self::DIRECTORY_DERIVED;

		$path = self::buildFilename($directory, $method, $suffix, $extension);

		if (\is_string($subDirectory) && strlen($subDirectory))
		{
			$pi = pathinfo($path);
			$path = $pi['dirname'] . '/' . $subDirectory . '/' . $pi['basename'];
			$directory .= '/' . $subDirectory;
		}

		self::createDirectoryPath($path);
		$this->derivedDataFiles->offsetSet($path, false);

		return $path;
	}

	private function buildFilename($directory, $method, $suffix, $extension)
	{
		if (preg_match('/.*\\\\(.*?)Test::test(.*)$/', $method, $m))
		{
			$cls = $m[1];
			$method = str_replace($cls, '', $m[2]);
		}
		elseif (preg_match('/.*\\\\(.*?)Test::(.*)$/', $method, $m))
		{
			$cls = $m[1];
			$method = '';
		}
		else
			throw new \Exception('Invalid method ' . $method);

		if (\is_string($suffix) && strlen($suffix))
			$method .= '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $suffix);

		$name = $cls . '_' . $method . '.' . $extension;
		$name = preg_replace('/_+/', '_', $name);

		return __DIR__ . '/' . $directory . '/' . $name;
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

	private function loadFile($file, $eol)
	{
		return $this->convertEndOfLine(file_get_contents($file), $eol);
	}

	private function convertEndOfLine($data, $eol)
	{
		$data = str_replace("\r\n", "\n", $data);
		$data = str_replace("\r", "\n", $data);

		if ($eol == 'crlf')
		{
			$data = str_replace("\n", "\r\n", $data);
		}
		elseif ($eol == 'cr')
		{
			$data = str_replace("\n", "\r", str_replace("\r\n", "\n", $data));
		}

		return $data;
	}

	/**
	 *
	 * @var array
	 */
	private $derivedDataFiles;
}