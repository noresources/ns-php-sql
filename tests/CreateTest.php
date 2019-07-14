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
		$ds = $this->getDatasource('types');
		$t = $ds['ns_unittests']['types'];
		$this->assertInstanceOf(TableStructure::class, $t);
		$builder = new GenericStatementBuilder();
		$context = new StatementContext($builder);
		$q = new CreateTableQuery($t);
		$sql = $q->buildExpression($context);
		//echo $sql;
	}

	private function getDatasource($name)
	{
		if ($this->datasources->offsetExists($name))
		{
			return $this->datasources[$name];
		}

		$filename = __DIR__ . '/data/sql/' . $name . '.xml';
		$ds = DatasourceStructure::create($filename);
		$this->assertInstanceOf(DatasourceStructure::class, $ds);
		$this->datasources->offsetSet($name, $ds);
		return $ds;
	}

	/**
	 * @var \ArrayObject
	 */
	private $datasources;
}