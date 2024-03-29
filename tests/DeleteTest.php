<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL;

use NoreSources\SQL\DBMS\Reference\ReferencePlatform;
use NoreSources\SQL\Syntax\Statement\StatementBuilder;
use NoreSources\SQL\Syntax\Statement\Manipulation\DeleteQuery;
use NoreSources\Test\DatasourceManager;
use NoreSources\Test\DerivedFileTestTrait;
use PHPUnit\Framework\TestCase;

final class DeleteTest extends TestCase
{

	use DerivedFileTestTrait;

	public function __construct($name = null, array $data = [],
		$dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->initializeDerivedFileTest(__DIR__);
		$this->datasources = new DatasourceManager();
	}

	public function testDeleteCompanyEmployees()
	{
		$structure = $this->datasources->get('Company');
		$tableStructure = $structure['ns_unittests']['Employees'];
		$this->assertInstanceOf(Structure\TableStructure::class,
			$tableStructure);
		$platform = new ReferencePlatform();
		$q = new DeleteQuery($tableStructure);

		$q->where([
			// Short form
			'id' => 1
		], "name='to be deleted'");

		StatementBuilder::getInstance(); // IDO workaround
		$result =  StatementBuilder::getInstance()($q, $platform, $tableStructure);

		$sql = \SqlFormatter::format(strval($result), false);

		$this->assertDerivedFile($sql, __METHOD__, null, 'sql');
	}

	/**
	 *
	 * @var DatasourceManager
	 */
	private $datasources;
}