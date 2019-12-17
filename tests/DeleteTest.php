<?php
namespace NoreSources\SQL;

use NoreSources\SQL\DBMS\Reference;
use NoreSources\SQL\Expression\TokenStream;
use NoreSources\SQL\Statement\BuildContext;
use PHPUnit\Framework\TestCase;

final class DeleteTest extends TestCase
{

	public function __construct()
	{
		parent::__construct();
		$this->derivedFileManager = new DerivedFileManager();
		$this->datasources = new DatasourceManager();
	}

	public function testDeleteCompanyEmployees()
	{
		$structure = $this->datasources->get('Company');
		$tableStructure = $structure['ns_unittests']['Employees'];
		$this->assertInstanceOf(TableStructure::class, $tableStructure);
		$builder = new Reference\StatementBuilder();
		$context = new BuildContext($builder);
		$context->setPivot($tableStructure);
		$q = new Statement\DeleteQuery($tableStructure);

		$q->where([
			// Short form
			'id' => 1
		], "name='to be deleted'");

		$stream = new TokenStream();
		$q->tokenize($stream, $context);
		$sql = $builder->finalize($stream, $context);
		$sql = \SqlFormatter::format(strval($sql), false);

		$this->derivedFileManager->assertDerivedFile($sql, __METHOD__, null, 'sql');
	}

	/**
	 *
	 * @var DatasourceManager
	 */
	private $datasources;

	/**
	 *
	 * @var DerivedFileManager
	 */
	private $derivedFileManager;
}