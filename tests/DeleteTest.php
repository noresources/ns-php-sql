<?php
namespace NoreSources\SQL;

use NoreSources\SQL\DBMS\Reference\ReferenceStatementBuilder;
use NoreSources\SQL\Expression\TokenStream;
use NoreSources\SQL\Statement\BuildContext;
use NoreSources\SQL\Statement\StatementBuilder;

final class DeleteTest extends \PHPUnit\Framework\TestCase
{

	public function __construct($name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->derivedFileManager = new DerivedFileManager();
		$this->datasources = new DatasourceManager();
	}

	public function testDeleteCompanyEmployees()
	{
		$structure = $this->datasources->get('Company');
		$tableStructure = $structure['ns_unittests']['Employees'];
		$this->assertInstanceOf(Structure\TableStructure::class, $tableStructure);
		$builder = new ReferenceStatementBuilder();
		$context = new BuildContext($builder);
		$context->setPivot($tableStructure);
		$q = new Statement\DeleteQuery($tableStructure);

		$q->where([
			// Short form
			'id' => 1
		], "name='to be deleted'");

		$stream = new TokenStream();
		$q->tokenize($stream, $context);
		$sql = StatementBuilder::finalize($stream, $context);
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