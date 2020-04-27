<?php
namespace NoreSources\SQL;

use NoreSources\SQL\DBMS\Reference\ReferenceStatementBuilder;
use NoreSources\SQL\Expression\TokenStream;
use NoreSources\SQL\Statement\StatementTokenStreamContext;
use NoreSources\SQL\Statement\Structure\CreateIndexQuery;
use NoreSources\SQL\Statement\Structure\CreateTableQuery;
use NoreSources\Test\DatasourceManager;
use NoreSources\Test\DerivedFileManager;

final class CreateTableTest extends \PHPUnit\Framework\TestCase
{

	public function __construct($name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->datasources = new DatasourceManager();
		$this->derivedFileManager = new DerivedFileManager(__DIR__);
	}

	public function testCreateIndex()
	{
		$structure = $this->datasources->get('Company');
		$tableStructure = $structure['ns_unittests']['Hierarchy'];
		$builder = new ReferenceStatementBuilder();

		$index = new CreateIndexQuery();
		$index->table('Hierarchy')
			->name('managed')
			->columns('manageeId')
			->where('managerId > 10');

		$context = new StatementTokenStreamContext($builder);
		$context->setPivot($tableStructure);
		$stream = new TokenStream();
		$index->tokenize($stream, $context);
		$data = $builder->finalizeStatement($stream, $context);

		$sql = \SqlFormatter::format(strval($data), false);
		$this->derivedFileManager->assertDerivedFile($sql, __METHOD__, null, 'sql');
	}

	public function testCreateTableCompanyTables()
	{
		$structure = $this->datasources->get('Company');
		$builder = new ReferenceStatementBuilder();

		foreach ([
			'Employees',
			'Hierarchy',
			'Tasks'
		] as $tableName)
		{
			$tableStructure = $structure['ns_unittests'][$tableName];
			$this->assertInstanceOf(Structure\TableStructure::class, $tableStructure,
				'Finding ' . $tableName);
			$context = new StatementTokenStreamContext($builder);
			$context->setPivot($tableStructure);
			$q = new CreateTableQuery($tableStructure);
			$stream = new TokenStream();
			$q->tokenize($stream, $context);
			$result = $builder->finalizeStatement($stream, $context);

			$sql = \SqlFormatter::format(strval($result), false);
			$this->derivedFileManager->assertDerivedFile($sql, __METHOD__, $tableName, 'sql',
				$tableName . ' SQL');
		}
	}

	private $datasources;

	/**
	 * /**
	 *
	 * @var DerivedFileManager
	 */
	private $derivedFileManager;
}