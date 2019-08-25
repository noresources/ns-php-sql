<?php

namespace NoreSources\SQL;

use PHPUnit\Framework\TestCase;

final class CreateTableTest extends TestCase
{
	const DIRECTORY_REFERENCE = 'reference';
	const DIRECTORY_DERIVED = 'derived';

	public function __construct()
	{
		parent::__construct();
		$this->datasources = new \ArrayObject();
		$this->derivedDataFiles = array ();
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

	public function testCreateTableBasic()
	{
		$serializer = $this->getDatasource('types');
		$t = $serializer['ns_unittests']['types'];
		$this->assertInstanceOf(TableStructure::class, $t);

		$builder = new GenericStatementBuilder();
		$context = new StatementContext($builder);
		$q = new CreateTableQuery($t);
		$sql = $q->buildExpression($context);

		$reference = $this->buildFilename(self::DIRECTORY_REFERENCE, __METHOD__, 'sql');
		if (is_file($reference))
		{
			$derived = $this->buildFilename(self::DIRECTORY_DERIVED, __METHOD__, 'sql');
			if ($this->saveDerivedFile($derived, $sql))
				$this->assertFileEquals($reference, $derived);
		}
		elseif ($this->createDirectoryPath($reference))
		{
			file_put_contents($reference, $sql);
		}
	}

	public function testCreateTableCompanyTask()
	{
		$serializer = $this->getDatasource('Company');
		$t = $serializer['ns_unittests']['Tasks'];
		$this->assertInstanceOf(TableStructure::class, $t);
		$builder = new GenericStatementBuilder();
		$context = new StatementContext($builder);
		$q = new CreateTableQuery($t);
		$sql = $q->buildExpression($context);

		$reference = $this->buildFilename(self::DIRECTORY_REFERENCE, __METHOD__, 'sql');
		if (is_file($reference))
		{
			$derived = $this->buildFilename(self::DIRECTORY_DERIVED, __METHOD__, 'sql');
			if ($this->saveDerivedFile($derived, $sql))
				$this->assertFileEquals($reference, $derived);
		}
		elseif ($this->createDirectoryPath($reference))
		{
			file_put_contents($reference, $sql);
		}
	}

	private function getDatasource($name)
	{
		if ($this->datasources->offsetExists($name))
			return $this->datasources[$name];

		$filename = __DIR__ . '/data/structures/' . $name . '.xml';
		$content = file_get_contents($filename);
		$serializer = new XMLStructureSerializer();
		//$serializer->unserialize($content);
		$serializer->unserialize($filename);
		$this->assertInstanceOf(DatasourceStructure::class, $serializer->structureElement);
		$this->datasources->offsetSet($name, $serializer->structureElement);
		return $serializer->structureElement;
	}

	/**
	 * @var \ArrayObject
	 */
	private $datasources;

	private function buildFilename($directory, $method, $extension)
	{
		$method = preg_replace(',.*::(.*),', '\1', $method);
		$method = preg_replace(',[^a-zA-Z0-9_-],', '_', $method);
		$cls = __CLASS__;
		$cls = preg_replace(',[^a-zA-Z0-9_-],', '_', $cls);
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

	private function saveDerivedFile($path, $data)
	{
		$result = $this->createDirectoryPath($path);

		if ($result)
		{
			$result = file_put_contents($path, $data);
			$this->assertNotFalse($result, 'Write derived data to ' . $path);
			$this->assertFileExists($path, 'Derived file exists');
			
			if ($result)
			{
				$this->derivedDataFiles[] = $path;
			}
		}

		return $result;
	}

	/**
	 * @var array
	 */
	private $derivedDataFiles;
}