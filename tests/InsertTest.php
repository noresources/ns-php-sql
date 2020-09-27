<?php
namespace NoreSources\SQL;

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\Reference\ReferencePlatform;
use NoreSources\SQL\Expression\ExpressionHelper;
use NoreSources\SQL\Expression\TokenizableExpressionInterface;
use NoreSources\SQL\Statement\StatementBuilder;
use NoreSources\SQL\Statement\StatementTokenStreamContext;
use NoreSources\SQL\Statement\Manipulation\InsertQuery;
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
		$this->assertInstanceOf(Structure\TableStructure::class, $t);

		$q = new InsertQuery($t);

		$builderFlags = array(
			'no_default' => [],
			'default_values' => [
				[
					[
						K::PLATFORM_FEATURE_INSERT,
						K::PLATFORM_FEATURE_DEFAULTVALUES
					],
					true
				]
			],
			'default_keyword' => [
				[
					[
						K::PLATFORM_FEATURE_INSERT,
						K::PLATFORM_FEATURE_DEFAULT
					],
					true
				]
			]
		);

		foreach ($builderFlags as $key => $platformFeatures)
		{
			$platformFeatures = new ReferencePlatform($platformFeatures);
			$context = new StatementTokenStreamContext(
				$platformFeatures);
			$context->setPivot($t);
			$result =  StatementBuilder::getInstance()($q, $context);

			$this->assertInstanceOf(
				Statement\StatementOutputDataInterface::class, $result,
				'Result is (at least) a Statement\StatementOutputDataInterface');
			$this->derivedFileManager->assertDerivedFile(
				strval($result), __METHOD__, $key, 'sql');
		}
	}

	public function testInsertCompanyTask()
	{
		$structure = $this->datasources->get('Company');
		$tableStructure = $structure['ns_unittests']['Tasks'];
		$this->assertInstanceOf(Structure\TableStructure::class,
			$tableStructure);
		$platform = new ReferencePlatform();
		StatementBuilder::getInstance(); // IDO workaround
		$context = new StatementTokenStreamContext($platform);

		$tests = array(
			'empty' => array(),
			'literals' => [
				'name' => [
					ExpressionHelper::literal('Test task'),
					null
				],
				'creationDateTime' => [
					ExpressionHelper::literal(
						\DateTime::createFromFormat(\DateTime::ISO8601,
							'2012-01-16T16:35:26+0100')),
					null
				]
			],
			'polish' => [
				'name' => [
					ExpressionHelper::literal('Random priority'),
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
				if ($value instanceof TokenizableExpressionInterface)
					$q[$column] = $value;
				else
					$q->setColumnValue($column, $value[0], $value[1]);
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