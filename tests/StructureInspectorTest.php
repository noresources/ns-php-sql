<?php
namespace NoreSources\SQL;

use NoreSources\Container\Container;
use NoreSources\SQL\Structure\Inspector\StructureInspector;
use NoreSources\Test\DatasourceManager;
use NoreSources\Test\DerivedFileManager;
use PHPUnit\Framework\TestCase;

final class StructureInspectorTest extends TestCase
{

	public function __construct($name = null, array $data = [],
		$dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->datasources = new DatasourceManager();
		$this->derivedFileManager = new DerivedFileManager(__DIR__);
	}

	public function testDependency()
	{
		$structure = $this->datasources->get('Company');

		$inspector = StructureInspector::getInstance();
		$list = $inspector->getReverseReferenceMap($structure);

		if (false)
			echo (PHP_EOL .
				Container::implode($list, PHP_EOL,
					function ($path, $a) {
						$s = $path . ' = [';
						$s .= Container::implodeValues($a,
							',' . PHP_EOL . "\t",
							function ($e) {
								$p = $e->getIdentifier()->getPath();
								if (!empty($p))
									return $p;
								$p = '';
								if ($e->getParentElement())
									$p = $e->getParentElement()
										->getIdentifier()
										->getPath();
								return $p . '.' . $e->getElementKey();
							});
						$s .= ']' . PHP_EOL;
						return $s;
					}));
	}

	/**
	 *
	 * @var DatasourceManager
	 */
	private $datasources;

	/**
	 * /**
	 *
	 * @var DerivedFileManager
	 */
	private $derivedFileManager;
}