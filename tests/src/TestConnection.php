<?php
namespace NoreSources\Test;

use NoreSources\DataTree;
use NoreSources\SQL\DBMS\ConnectionHelper;

class TestConnection extends \PHPUnit\Framework\TestCase
{

	public function __construct()
	{
		$this->connections = new \ArrayObject();
		$this->files = new \ArrayObject();

		$basePath = __DIR__ . '/../settings';
		if (\is_dir($basePath))
		{
			$basePath = realpath($basePath);
			$iterator = opendir($basePath);
			while ($item = readdir($iterator))
			{
				$path = $basePath . '/' . $item;
				if (\is_file($path) && (\preg_match('/\.php$/', $path)) &&
					(\strpos($item, '.example.php') === false))
				{
					$key = pathinfo($path, PATHINFO_FILENAME);
					$this->files[$key] = $path;
				}
			}
			closedir($iterator);
		}
	}

	public function getAvailableConnectionNames()
	{
		return \array_keys($this->files->getArrayCopy());
	}

	public function get($name)
	{
		if ($this->connections->offsetExists($name))
			return $this->connections[$name];

		if (!$this->files->offsetExists($name))
			return null;

		$parameters = new DataTree();
		$parameters->load($this->files[$name]);

		$this->connections[$name] = ConnectionHelper::createConnection($parameters);
		return $this->connections[$name];
	}

	/**
	 *
	 * @var \ArrayObject
	 */
	private $connections;

	/**
	 *
	 * @var \ArrayObject
	 */
	private $files;
}
