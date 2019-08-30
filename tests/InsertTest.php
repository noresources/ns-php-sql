<?php

namespace NoreSources\SQL;

use PHPUnit\Framework\TestCase;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\ExpressionEvaluator as X;

final class InsertTest extends TestCase
{

	public function __construct()
	{
		parent::__construct();
		$this->datasources = new \ArrayObject();
		$this->derivedFileManager = new \DerivedFileManager();
	}

	public function testInsertBasic()
	{
		$serializer = $this->getDatasource('types');
		$t = $serializer['ns_unittests']['types'];
		$this->assertInstanceOf(TableStructure::class, $t);

		$q = new InsertQuery($t);

		$builderFlags = array (
				'no_default' => 0,
				'default_values' => K::BUILDER_INSERT_DEFAULT_VALUES,
				'default_keyword' => K::BUILDER_INSERT_DEFAULT_KEYWORD
		);

		foreach ($builderFlags as $key => $flags)
		{
			$builder = new GenericStatementBuilder($flags);
			$context = new StatementContext($builder);
			$sql = $q->buildExpression($context);

			$this->derivedFileManager->assertDerivedFile($sql, __METHOD__, $key, 'sql');
		}
	}

	public function testInsertCompanyTask()
	{
		$serializer = $this->getDatasource('Company');
		$t = $serializer['ns_unittests']['Tasks'];
		$this->assertInstanceOf(TableStructure::class, $t);
		$builder = new GenericStatementBuilder();
		$context = new StatementContext($builder);

		$tests = array (
				'empty' => array (),
				'literals' => array (
						'name' => X::literal('Test task'),
						'creationDateTime' => X::literal(\DateTime::createFromFormat(\DateTime::ISO8601, '2012-01-16T16:35:26+0100'))
				),
				'polish' => [
						'name' => X::literal('Random priority'),
						'priority' => ['rand()' => [1, 10]]
				], 'expression' => [
					'creator' => 1,
					'name' => "substr ('Lorem ipsum', 0, 5)"
				]
		);

		foreach ($tests as $key => $values)
		{
			$q = new InsertQuery($t);

			foreach ($values as $column => $value)
			{
				if ($value instanceof Expression)
					$q[$column] = $value;
				else
					$q->set($column, $value);
			}

			$sql = $q->buildExpression($context);

			$this->derivedFileManager->assertDerivedFile($sql, __METHOD__, $key, 'sql');
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

	

	/**
	 * 
	 * @var \DerivedFileManager
	 */
	private $derivedFileManager;
}