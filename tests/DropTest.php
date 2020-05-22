<?php
namespace NoreSources\SQL;

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\Reference\ReferenceStatementBuilder;
use NoreSources\SQL\Expression\TokenStream;
use NoreSources\SQL\Statement\StatementTokenStreamContext;
use NoreSources\SQL\Statement\Structure\DropIndexQuery;
use NoreSources\SQL\Statement\Structure\DropTableQuery;
use NoreSources\SQL\Statement\Structure\DropViewQuery;
use NoreSources\SQL\Structure\NamespaceStructure;
use NoreSources\Test\DatasourceManager;
use NoreSources\Test\DerivedFileManager;

final class DropTest extends \PHPUnit\Framework\TestCase
{

	public function __construct($name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->datasources = new DatasourceManager();
		$this->derivedFileManager = new DerivedFileManager(__DIR__);
	}

	public function testDropIndex()
	{
		$builder = new ReferenceStatementBuilder();

		$structureless = new DropIndexQuery();
		$structureless->identifier('structureless');
		$context = new StatementTokenStreamContext($builder);
		$stream = new TokenStream();
		$structureless->tokenize($stream, $context);
		$result = $builder->finalizeStatement($stream, $context);
		$sql = \SqlFormatter::format(strval($result), false);
		$this->derivedFileManager->assertDerivedFile($sql, __METHOD__, 'structureless', 'sql',
			'Structureless SQL');

		$structure = $this->datasources->get('Company');
		$indexStructure = $structure['ns_unittests']['index_employees_name'];
		$structured = new DropIndexQuery();
		$structured->identifier('structureless');
		$context = new StatementTokenStreamContext($builder, $indexStructure);
		$stream = new TokenStream();
		$structured->tokenize($stream, $context);
		$result = $builder->finalizeStatement($stream, $context);
		$sql = \SqlFormatter::format(strval($result), false);
		$this->derivedFileManager->assertDerivedFile($sql, __METHOD__, 'structured', 'sql',
			'Drop index SQL');
	}

	public function testDropView()
	{
		$builder = new ReferenceStatementBuilder(
			[
				K::BUILDER_DOMAIN_GENERIC => K::BUILDER_SCOPED_STRUCTURE_DECLARATION
			]);

		$view = new DropViewQuery();
		$view->identifier('Males');
		$context = new StatementTokenStreamContext($builder);
		$stream = new TokenStream();
		$view->tokenize($stream, $context);
		$result = $builder->finalizeStatement($stream, $context);
		$sql = \SqlFormatter::format(strval($result), false);
		$this->derivedFileManager->assertDerivedFile($sql, __METHOD__, 'structureless', 'sql');

		$structure = $this->datasources->get('Company')['ns_unittests'];
		$this->assertInstanceOf(NamespaceStructure::class, $structure);
		$context = new StatementTokenStreamContext($builder, $structure);
		$this->assertInstanceOf(NamespaceStructure::class, $context->getPivot());
		$stream = new TokenStream();
		$view->tokenize($stream, $context);
		$result = $builder->finalizeStatement($stream, $context);
		$sql = \SqlFormatter::format(strval($result), false);
		$this->derivedFileManager->assertDerivedFile($sql, __METHOD__, 'structure', 'sql');
	}

	public function testDropTableCompanyTables()
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
			$q = new DropTableQuery($tableStructure);
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