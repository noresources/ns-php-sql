<?php
namespace NoreSources\SQL;

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\Reference\ReferencePlatform;
use NoreSources\SQL\Structure\NamespaceStructure;
use NoreSources\SQL\Syntax\Statement\StatementBuilder;
use NoreSources\SQL\Syntax\Statement\Structure\DropIndexQuery;
use NoreSources\SQL\Syntax\Statement\Structure\DropNamespaceQuery;
use NoreSources\SQL\Syntax\Statement\Structure\DropTableQuery;
use NoreSources\SQL\Syntax\Statement\Structure\DropViewQuery;
use NoreSources\Test\DatasourceManager;
use NoreSources\Test\DerivedFileManager;

final class DropTest extends \PHPUnit\Framework\TestCase
{

	public function __construct($name = null, array $data = [],
		$dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->datasources = new DatasourceManager();
		$this->derivedFileManager = new DerivedFileManager(__DIR__);
	}

	public function testDropIndex()
	{
		$structurelessEnvironment = new Environment();
		$platform = $structurelessEnvironment->getPlatform();

		$structureless = new DropIndexQuery();
		$structureless->identifier('structureless');

		$result = $structurelessEnvironment->prepareStatement(
			$structureless);
		$sql = \SqlFormatter::format(strval($result), false);
		$this->derivedFileManager->assertDerivedFile($sql, __METHOD__,
			'structureless', 'sql', 'Structureless SQL');

		$structure = $this->datasources->get('Company');
		$structuredEnvironment = new Environment($structure);
		$indexStructure = $structure['ns_unittests']['index_employees_name'];
		$structured = $structuredEnvironment->newStatement(
			K::QUERY_DROP_INDEX);
		$structured->identifier('structureless');

		$result = $structuredEnvironment->prepareStatement($structured);
		$sql = \SqlFormatter::format(strval($result), false);
		$this->derivedFileManager->assertDerivedFile($sql, __METHOD__,
			'structured', 'sql', 'Drop index SQL');
	}

	public function testDropView()
	{
		$platform = new ReferencePlatform([], []);

		$view = new DropViewQuery();
		$view->identifier('Males');
		$result =  StatementBuilder::getInstance()($view, $platform);
		$sql = \SqlFormatter::format(strval($result), false);
		$this->derivedFileManager->assertDerivedFile($sql, __METHOD__,
			'structureless', 'sql');

		$structure = $this->datasources->get('Company')['ns_unittests'];
		$this->assertInstanceOf(NamespaceStructure::class, $structure);
		$result =  StatementBuilder::getInstance()($view, $platform, $structure);
		$sql = \SqlFormatter::format(strval($result), false);
		$this->derivedFileManager->assertDerivedFile($sql, __METHOD__,
			'structure', 'sql');
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
		$q = $platform->newStatement(K::QUERY_DROP_NAMESPACE);
		$data = $environment->prepareStatement($q);
		$sql = \SqlFormatter::format(strval($data), false);
		$this->derivedFileManager->assertDerivedFile($sql, __METHOD__,
			'using-structure', 'sql');

		$q->identifier('ns');
		$data = $environment->prepareStatement($q);
		$sql = \SqlFormatter::format(strval($data), false);
		$this->derivedFileManager->assertDerivedFile($sql, __METHOD__,
			'using-identifier', 'sql');
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
			$sql = \SqlFormatter::format(strval($result), false);
			$this->derivedFileManager->assertDerivedFile($sql,
				__METHOD__, $tableName, 'sql', $tableName . ' SQL');
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