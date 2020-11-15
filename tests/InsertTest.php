<?php
namespace NoreSources\SQL;

use NoreSources\Expression\ExpressionInterface;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\Reference\ReferencePlatform;
use NoreSources\SQL\Structure\TableStructure;
use NoreSources\SQL\Syntax\Data;
use NoreSources\SQL\Syntax\Statement\StatementBuilder;
use NoreSources\SQL\Syntax\Statement\StatementOutputDataInterface;
use NoreSources\SQL\Syntax\Statement\StatementTokenStreamContext;
use NoreSources\SQL\Syntax\Statement\Manipulation\InsertQuery;
use NoreSources\Test\DatasourceManager;
use NoreSources\Test\DerivedFileManager;

final class InsertTest extends \PHPUnit\Framework\TestCase
{

	public function __construct($name = null, array $data = [],
		$dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->datasources = new DatasourceManager();
		$this->derivedFileManager = new DerivedFileManager(__DIR__);
	}

	public function testInsertBasic()
	{
		$structure = $this->datasources->get('types');
		$t = $structure['ns_unittests']['types'];
		$this->assertInstanceOf(TableStructure::class, $t);

		$q = new InsertQuery($t);

		$builderFlags = array(
			'no_default' => [],
			'default_values' => [
				[
					[
						K::FEATURE_INSERT,
						K::FEATURE_DEFAULTVALUES
					],
					true
				]
			],
			'default_keyword' => [
				[
					[
						K::FEATURE_INSERT,
						K::FEATURE_DEFAULT
					],
					true
				]
			]
		);

		foreach ($builderFlags as $key => $platformFeatures)
		{
			$platformFeatures = new ReferencePlatform([],
				$platformFeatures);
			$context = new StatementTokenStreamContext(
				$platformFeatures);
			$context->setPivot($t);
			$result =  StatementBuilder::getInstance()($q, $context);

			$this->assertInstanceOf(StatementOutputDataInterface::class,
				$result,
				'Result is (at least) a StatementOutputDataInterface');
			$this->derivedFileManager->assertDerivedFile(
				strval($result), __METHOD__, $key, 'sql');
		}
	}

	public function testInsertCompanyTask()
	{
		$structure = $this->datasources->get('Company');
		$tableStructure = $structure['ns_unittests']['Tasks'];
		$this->assertInstanceOf(TableStructure::class, $tableStructure);
		$platform = new ReferencePlatform();
		StatementBuilder::getInstance(); // IDO workaround
		$context = new StatementTokenStreamContext($platform);

		$tests = array(
			'empty' => array(),
			'literals' => [
				'name' => [
					new Data('Test task'),
					null
				],
				'creationDateTime' => [
					new Data(
						\DateTime::createFromFormat(\DateTime::ISO8601,
							'2012-01-16T16:35:26+0100')),
					null
				]
			],
			'polish' => [
				'name' => [
					new Data('Random priority'),
					null
				],
				'priority' => [
					[
						'rand()' => [
							1,
							10
						]
					],
					null
				]
			],
			'expression' => [
				'creator' => [
					1,
					true
				],
				'name' => [
					"substr (
					'Lorem ipsum', 0, 5)",
					true
				]
			]
		);

		foreach ($tests as $key => $values)
		{
			$q = new InsertQuery($tableStructure);
			$context->setPivot($tableStructure);

			foreach ($values as $column => $value)
			{
				if ($value instanceof ExpressionInterface)
					$q[$column] = $value;
				else
					$q->setColumnData($column, $value[0], $value[1]);
			}

			$result =  StatementBuilder::getInstance() ($q, $context);

			$this->derivedFileManager->assertDerivedFile(
				strval($result), __METHOD__, $key, 'sql');
		}
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