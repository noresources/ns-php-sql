<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL;

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\Reference\ReferenceConnection;
use NoreSources\SQL\DBMS\Reference\ReferencePlatform;
use NoreSources\SQL\Structure\CheckTableConstraint;
use NoreSources\SQL\Structure\ColumnStructure;
use NoreSources\SQL\Structure\DatasourceStructure;
use NoreSources\SQL\Structure\ForeignKeyTableConstraint;
use NoreSources\SQL\Structure\NamespaceStructure;
use NoreSources\SQL\Structure\PrimaryKeyTableConstraint;
use NoreSources\SQL\Structure\TableStructure;
use NoreSources\SQL\Syntax\Data;
use NoreSources\SQL\Syntax\Statement\StatementBuilder;
use NoreSources\SQL\Syntax\Statement\Query\SelectQuery;
use NoreSources\SQL\Syntax\Statement\Structure\CreateIndexQuery;
use NoreSources\SQL\Syntax\Statement\Structure\CreateTableQuery;
use NoreSources\SQL\Syntax\Statement\Structure\CreateViewQuery;
use NoreSources\Test\DatasourceManager;
use NoreSources\Test\DerivedFileTestTrait;
use NoreSources\Test\SqlFormatter;
use PHPUnit\Framework\TestCase;

final class CreateTest extends TestCase
{

	use DerivedFileTestTrait;

	public function __construct($name = null, array $data = [],
		$dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->datasources = new DatasourceManager();
		$this->initializeDerivedFileTest(__DIR__);
	}

	public function testCreateIndex()
	{
		$structure = $this->datasources->get('Company');
		$tableStructure = $structure['ns_unittests']['Hierarchy'];
		$platform = new ReferencePlatform();

		$index = new CreateIndexQuery();
		$index->table('Hierarchy')
			->identifier('managed')
			->columns('manageeId')
			->where('managerId > 10');

		$data = StatementBuilder::getInstance()($index, $platform, $tableStructure);

		$sql = SqlFormatter::format(strval($data), false);
		$this->assertDerivedFile($sql, __METHOD__, null, 'sql');
	}

	public function testCreateView()
	{
		$structure = $this->datasources->get('Company');
		$tableStructure = $structure['ns_unittests']['Employees'];
		$platform = new ReferencePlatform([],
			[
				[
					[
						K::FEATURE_CREATE,
						K::FEATURE_CREATE_FLAGS
					],
					K::FEATURE_CREATE_TEMPORARY
				]
			]);

		$select = new SelectQuery($tableStructure);
		$select->columns('id', 'name')->where([
			'gender' => "'M'"
		]);

		$view = new CreateViewQuery();
		$view->identifier('Males')
			->flags(CreateViewQuery::TEMPORARY)
			->select($select);

		$data =  StatementBuilder::getInstance()($view, $platform, $tableStructure);

		$sql = SqlFormatter::format(strval($data), false);
		$this->assertDerivedFile($sql, __METHOD__, null, 'sql');
	}

	public function testCreateIndexFromStructure()
	{
		$structure = $this->datasources->get('Company');
		$platform = new ReferencePlatform();

		/**
		 *
		 * @var TableStructure $employees
		 */
		$employees = $structure['ns_unittests']['Employees'];
		$this->assertInstanceOf(TableStructure::class, $employees);

		/**
		 *
		 * @todo
		 */
		if (false)
		{
			$constraints = $employees->getConstraints();

			$index = null;
			foreach ($constraints as $c)
			{
				if ($c->getName() == 'index_employees_name')
					$constraint = $c;
			}

			$this->assertInstanceOf(IndexTableConstraint::class,
				$constraint);

			/**
			 *
			 * @todo
			 */
			$q = new CreateIndexQuery();
			$q->setFromTable($employees, $constraint->getName());
			$result = StatementBuilder::getInstance()->build($q,
				$platform, $employees);

			$sql = SqlFormatter::format(strval($result), false);
			$this->assertDerivedFile($sql, __METHOD__, null, 'sql');
		}
	}

	public function testCreateTableConstraint()
	{
		$structure = new DatasourceStructure();
		$metavariable = new NamespaceStructure('metavariables',
			$structure);
		$foo = new TableStructure('foo', $metavariable);
		{
			$id = new ColumnStructure('id');
			$id->setColumnProperty(K::COLUMN_DATA_TYPE,
				K::DATATYPE_INTEGER);
			$id->setColumnProperty(K::COLUMN_FLAGS,
				K::COLUMN_FLAG_AUTO_INCREMENT);
			$foo->appendElement($id);

			$angle = new ColumnStructure('angle');
			$angle->setColumnProperty(K::COLUMN_DATA_TYPE,
				K::DATATYPE_REAL);
			$angle->setColumnProperty(K::COLUMN_DEFAULT_VALUE,
				new Data(pi()));
			$foo->appendElement($angle);

			$foo->addConstraint(
				new PrimaryKeyTableConstraint([
					'id'
				], 'pirmary_foo'));

			$checkPi = new CheckTableConstraint('pi_boundary');
			$checkPi->where([
				'between' => [
					'angle',
					-pi(),
					pi()
				]
			]);
			$foo->addConstraint($checkPi);
			$metavariable->appendElement($foo);
		}

		$bar = new TableStructure('bar', $metavariable);
		{
			$key = new ColumnStructure('key');
			$valueId = new ColumnStructure('valueId');
			$valueId->setColumnProperty(K::COLUMN_DATA_TYPE,
				K::DATATYPE_INTEGER | K::DATATYPE_NULL);
			$bar->appendElement($valueId);

			$bar->addConstraint(
				new PrimaryKeyTableConstraint([
					'key'
				]));

			$bar->addConstraint(
				$fk = new ForeignKeyTableConstraint($foo, [], 'fk'));
			$fk->addColumn('valueId', 'id');
			$metavariable->appendElement($bar);
		}
		$environment = new Environment(new ReferenceConnection(),
			$structure);

		/**
		 *
		 * @var CreateTableQuery $createTable
		 */
		$createTable = $environment->getPlatform()->newStatement(
			CreateTableQuery::class);

		foreach ([
			$foo,
			$bar
		] as $table)
		{
			$createTable->table($table);
			$data = $environment->prepareStatement($createTable);
			$sql = SqlFormatter::format(\strval($data), false);
			$this->assertDerivedFile($sql, __METHOD__, $table->getName(),
				'sql');
		}
	}

	public function testCreateTableCompanyTables()
	{
		$structure = $this->datasources->get('Company');
		$platform = new ReferencePlatform([],
			[
				'or replace' => [
					[
						K::FEATURE_CREATE,
						K::FEATURE_ELEMENT_TABLE,
						K::FEATURE_CREATE_FLAGS
					],
					K::FEATURE_CREATE_REPLACE
				]
			]);

		foreach ([
			'Employees',
			'Hierarchy',
			'Tasks'
		] as $tableName)
		{
			$tableStructure = $structure['ns_unittests'][$tableName];
			$this->assertInstanceOf(Structure\TableStructure::class,
				$tableStructure, 'Finding ' . $tableName);
			$q = new CreateTableQuery($tableStructure);
			$q->flags(CreateTableQuery::REPLACE);
			$result =  StatementBuilder::getInstance()($q, $platform, $tableStructure);

			$sql = SqlFormatter::format(strval($result), false);
			$this->assertDerivedFile($sql, __METHOD__, $tableName, 'sql',
				$tableName . ' SQL');
		}
	}

	private $datasources;
}