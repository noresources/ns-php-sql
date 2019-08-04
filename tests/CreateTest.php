<?php

namespace NoreSources\SQL;

use PHPUnit\Framework\TestCase;

final class CreateTableTest extends TestCase
{

	public function __construct()
	{
		parent::__construct();
		$this->datasources = new \ArrayObject();
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
		echo $sql;
	}

	private function getDatasource($name)
	{
		if ($this->datasources->offsetExists($name))
		{
			return $this->datasources[$name];
		}

		$filename = __DIR__ . '/data/structures/' . $name . '.xml';
		$serializer = new XMLStructureSerializer();
		$serializer->unserialize(file_get_contents($filename));
		$this->assertInstanceOf(DatasourceStructure::class, $serializer->structureElement);
		$this->datasources->offsetSet($name, $serializer->structureElement);
		return $serializer->structureElement;
	}

	/**
	 * @var \ArrayObject
	 */
	private $datasources;
}