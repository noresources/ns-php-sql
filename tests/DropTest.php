<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL;

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\Reference\ReferencePlatform;
use NoreSources\SQL\Structure\NamespaceStructure;
use NoreSources\SQL\Structure\ViewStructure;
use NoreSources\SQL\Syntax\Statement\StatementBuilder;
use NoreSources\SQL\Syntax\Statement\Structure\DropIndexQuery;
use NoreSources\SQL\Syntax\Statement\Structure\DropNamespaceQuery;
use NoreSources\SQL\Syntax\Statement\Structure\DropTableQuery;
use NoreSources\SQL\Syntax\Statement\Structure\DropViewQuery;
use NoreSources\Test\DatasourceManager;
use NoreSources\Test\DatasourceManagerTrait;
use NoreSources\Test\DerivedFileTestTrait;
use NoreSources\Test\SqlFormatter;
use PHPUnit\Framework\TestCase;

final class DropTest extends TestCase
{
	use DatasourceManagerTrait;
	use DerivedFileTestTrait;

	public function __construct($name = null, array $data = [],
		$dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->datasources = new DatasourceManager();
		$this->initializeDerivedFileTest(__DIR__);
	}

	public function testDropIndex()
	{
		$structurelessEnvironment = new Environment();

		$platform = $structurelessEnvironment->getPlatform();

		/** @var DropIndexQuery $structureless */
		$structureless = $platform->newStatement(DropIndexQuery::class);
		$structureless->identifier('structureless');

		$result = $structurelessEnvironment->prepareStatement(
			$structureless);
		$sql = SqlFormatter::format(strval($result), false);
		$this->assertDerivedFile($sql, __METHOD__, 'structureless',
			'sql', 'Structureless SQL');

		$structure = $this->datasources->get('Company');
		$structuredEnvironment = new Environment($structure);

		$indexStructure = $structure['ns_unittests']['Employees']['index_employees_name'];
		$structured = $structuredEnvironment->newStatement(
			DropIndexQuery::class);
		$structured->identifier('structureless');

		$result = $structuredEnvironment->prepareStatement($structured);
		$sql = SqlFormatter::format(strval($result), false);
		$this->assertDerivedFile($sql, __METHOD__, 'structured', 'sql',
			'Drop index SQL');
	}

	public function testDropView()
	{
		$platform = new ReferencePlatform([], []);

		$query = new DropViewQuery();
		$query->identifier('Males');
		$result =  StatementBuilder::getInstance()($query, $platform);
		$sql = SqlFormatter::format(strval($result), false);
		$this->assertDerivedFile($sql, __METHOD__, 'structureless',
			'sql');

		$structure = $this->datasources->get('Company')['ns_unittests'];
		$this->assertInstanceOf(NamespaceStructure::class, $structure);

		/** @var NamespaceStructure $structure */
		$structure->appendElement(new ViewStructure('Males'));

		$view = new ViewStructure('Males');
		$this->assertEquals('Males', $view->getName(),
			'Temporary view structure name');

		// Trick
		$structure->appendElement($view);
		$this->assertEquals($structure, $view->getParentElement(),
			'Temporary view parent');

		$this->assertEquals('ns_unittests.Males',
			\strval($view->getIdentifier()), 'Temporary view identifier');

		$result =  StatementBuilder::getInstance()($query, $platform, $view);
		$sql = SqlFormatter::format(strval($result), false);
		$this->assertDerivedFile($sql, __METHOD__, 'structure', 'sql');

		$result =  StatementBuilder::getInstance()($query, $platform, $structure);
		$sql = SqlFormatter::format(strval($result), false);
		$this->assertDerivedFile($sql, __METHOD__, 'parentstructure',
			'sql');
	}

	public function testDropNamespaceQuery()
	{
		$structure = $this->datasources->get('Company');
		$environment = new Environment($structure);

		$platform = new ReferencePlatform([], []);

		/**
		 *
		 * @var DropNamespaceQuery
		 */
		$q = $platform->newStatement(DropNamespaceQuery::class);
		$q->identifier('ns_unittests');
		$data = $environment->prepareStatement($q);
		$sql = SqlFormatter::format(strval($data), false);
		$this->assertDerivedFile($sql, __METHOD__, 'using-structure',
			'sql');

		$q->identifier('ns');
		$data = $environment->prepareStatement($q);
		$sql = SqlFormatter::format(strval($data), false);
		$this->assertDerivedFile($sql, __METHOD__, 'using-identifier',
			'sql');
	}

	public function testDropTableCompanyTables()
	{
		$structure = $this->datasources->get('Company');
		$platform = new ReferencePlatform();

		StatementBuilder::getInstance(); // IDO workaround
		foreach ([
			'Employees',
			'Hierarchy',
			'Tasks'
		] as $tableName)
		{
			$tableStructure = $structure['ns_unittests'][$tableName];
			$this->assertInstanceOf(Structure\TableStructure::class,
				$tableStructure, 'Finding ' . $tableName);

			/**
			 *
			 * @var DropTableQuery $d
			 */
			$q = new DropTableQuery($tableStructure);

			$q->dropFlags($q->getDropFlags() | K::DROP_CASCADE);

			$result =  StatementBuilder::getInstance()($q, $platform, $tableName);
			$sql = SqlFormatter::format(strval($result), false);
			$this->assertDerivedFile($sql, __METHOD__, $tableName, 'sql',
				$tableName . ' SQL');
		}
	}
}