<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Test\Common;

use NoreSources\DateTime;
use NoreSources\Container\Container;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Environment;
use NoreSources\SQL\DBMS\ConnectionInterface;
use NoreSources\SQL\DBMS\PreparedStatementInterface;
use NoreSources\SQL\DBMS\Configuration\ConfiguratorInterface;
use NoreSources\SQL\DBMS\PDO\PDOConnection;
use NoreSources\SQL\Result\Recordset;
use NoreSources\SQL\Structure\ArrayColumnDescription;
use NoreSources\SQL\Structure\StructureElementInterface;
use NoreSources\SQL\Structure\TableStructure;
use NoreSources\SQL\Syntax\CastFunction;
use NoreSources\SQL\Syntax\Data;
use NoreSources\SQL\Syntax\Keyword;
use NoreSources\SQL\Syntax\Parameter;
use NoreSources\SQL\Syntax\TimestampFormatFunction;
use NoreSources\SQL\Syntax\Statement\Manipulation\DeleteQuery;
use NoreSources\SQL\Syntax\Statement\Query\SelectQuery;
use NoreSources\SQL\Syntax\Statement\Structure\CreateTableQuery;
use NoreSources\Test\ConnectionHelper;
use NoreSources\Test\DatasourceManagerTrait;
use NoreSources\Test\DerivedFileTestTrait;
use NoreSources\Test\Generator;
use NoreSources\Test\UnittestConnectionManagerTrait;
use PHPUnit\Framework\TestCase;

final class TimestampTest extends TestCase
{
	use UnittestConnectionManagerTrait;
	use DatasourceManagerTrait;
	use DerivedFileTestTrait;

	const NAMESPACE_NAME = 'ns_unittests';

	public function __construct($name = null, array $data = [],
		$dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->initializeDatasourceManager(__DIR__ . '/..');
		$this->initializeDerivedFileTest(__DIR__ . '/..');
	}

	public function testCurrentTimestamp()
	{
		$this->runConnectionTest(__METHOD__,
			function (ConnectionInterface $c) {
				return $c->getConfigurator()
					->canGet(K::CONFIGURATION_TIMEZONE);
			});
	}

	private function dbmsCurrentTimestamp(
		ConnectionInterface $connection, $dbmsName, $method)
	{
		$platform = $connection->getPlatform();
		$environment = new Environment($connection);
		$configurator = $connection->getConfigurator();

		$this->assertInstanceOf(ConfiguratorInterface::class,
			$configurator, $dbmsName . ' configurator');

		$systemTimezone = new \DateTimeZone(date_default_timezone_get());

		if ($configurator->canSet(K::CONFIGURATION_TIMEZONE))
		{
			$targetTimezone = new \DateTimeZone('America/New_York');
			$configurator->offsetSet(K::CONFIGURATION_TIMEZONE,
				$targetTimezone);
		}
		$dbmsTimezone = $configurator->get(K::CONFIGURATION_TIMEZONE);
		if ($configurator->canSet(K::CONFIGURATION_TIMEZONE))
		{
			$expected = new DateTime('now', $targetTimezone);
			$actual = new DateTime('now', $dbmsTimezone);
			$this->assertEquals($expected->format('P'),
				$actual->format('P'), $dbmsName . ' set timezone');
		}

		/** @var SelectQuery $select */
		$select = $platform->newStatement(SelectQuery::class);

		$select->columns(new Keyword(K::KEYWORD_CURRENT_TIMESTAMP));
		$statement = $environment->prepareStatement($select);

		/** @var Recordset $recordset */
		$recordset = $environment->executeStatement($statement);
		$recordset->setFlags(
			K::RECORDSET_FETCH_UNSERIALIZE | K::RECORDSET_FETCH_INDEXED);
		$this->assertInstanceOf(Recordset::class, $recordset,
			$dbmsName . \strval($statement) . ' result type');
		$column = $recordset->getResultColumns()->get(0);
		$row = $recordset->current();
		$value = $row[0];

		$this->assertInstanceOf(\DateTimeInterface::class, $value,
			$dbmsName . ' current_timestamp is a ' .
			\DateTimeInterface::class);
		$utcNow = new \DateTime('now', DateTime::getUTCTimezone());
		$now = new DateTime('now', $systemTimezone);

		$valueDifference = abs(
			$now->getTimestamp() - $value->getTimestamp());

		$this->assertTrue(($valueDifference < 2),
			sprintf("%s\n%-20.20s: %s (%s)\n%-20.20s: %s (%s)",
				$dbmsName . ' current timestamp value', 'expected',
				$now->format(DateTime::ISO8601),
				$systemTimezone->getName(), 'current_timestamp',
				$value->format(DateTime::ISO8601),
				$dbmsTimezone->getName()));

		$this->assertEquals($systemTimezone->getOffset($utcNow),
			$value->getTimezone()
				->getOffset($utcNow),
			$dbmsName . ' value timezone offset');
	}

	public function testTimezoneSerialization()
	{
		foreach ([
			'UTC',
			'America/New_York',
			'Asia/Tokyo'
		] as $timezone)
		{
			$this->runConnectionTest(__METHOD__,
				function (ConnectionInterface $c) {
					return $c->getConfigurator()
						->canGet(K::CONFIGURATION_TIMEZONE);
				}, [
					$timezone
				]);
		}
	}

	private function dbmsTimezoneSerialization(
		ConnectionInterface $connection, $dbmsName, $method, $timezone)
	{
		if ($connection->getConfigurator()->canSet(
			K::CONFIGURATION_TIMEZONE))
			$connection->getConfigurator()->offsetSet(
				K::CONFIGURATION_TIMEZONE, $timezone);
		else
		{
			$dbmsTimezone = $connection->getConfigurator()->get(
				K::CONFIGURATION_TIMEZONE);
			if ($dbmsTimezone->getName() != $timezone)
			{
				$this->assertFalse(false, 'Cannot set time zone');
				return;
			}
		}

		$dbmsName = $this->getDBMSName($connection);
		$method = ($method ? $method : $this->getMethodName(2));
		$platform = $connection->getPlatform();

		$structure = $this->getDatasource('types');
		$tableStructure = $structure['ns_unittests']['types'];

		/** @var CreateTableQuery $create */
		$create = $platform->newStatement(CreateTableQuery::class);
		$create->forStructure($tableStructure);
		$data = ConnectionHelper::buildStatement($connection, $create,
			$tableStructure);
		$sql = \SqlFormatter::format(strval($data), false);
		$this->assertDerivedFile($sql, $method,
			$dbmsName . '_' . $timezone, 'sql');
	}

	public function testTimestampFormats()
	{
		$this->runConnectionTest(__METHOD__,
			function ($c) {
				return !($c instanceof PDOConnection);
			});
	}

	private function dbmsTimestampFormats(
		ConnectionInterface $connection, $dbmsName, $method)
	{
		$this->setTimezone($connection, 'UTC');
		$structure = $this->getDatasource('types');
		$this->assertInstanceOf(StructureElementInterface::class,
			$structure);
		$tableStructure = $structure['ns_unittests']['types'];
		$this->assertInstanceOf(TableStructure::class, $tableStructure);

		$this->recreateTable($connection, $tableStructure, $method,
			false);

		$timestamps = [];
		for ($i = 0; $i < 10; $i++)
		{
			$timestamps[] = Generator::randomDateTime(
				[
					'yearRange' => [
						1789,
						2049
					],

					'timezone' => DateTime::getUTCTimezone()
				]);
		}

		// Some static timestamps
		$timestamps['UNIX epoch'] = new DateTIme('@0',
			DateTIme::getUTCTimezone());

		$timestamps['A year where "Y" (1806) and "o" (1807) differ'] = new DateTime(
			'1806-12-29T23:02:01+0000');

		$formats = DateTime::getFormatTokenDescriptions();

		$delete = $connection->getPlatform()->newStatement(
			DeleteQuery::class);
		$delete->table($tableStructure);
		$delete = ConnectionHelper::prepareStatement($connection,
			$delete, $tableStructure);

		$valueDataType = K::DATATYPE_TIMESTAMP;
		$columnType = new ArrayColumnDescription(
			[
				K::COLUMN_DATA_TYPE => $valueDataType
			]);

		foreach ($formats as $format => $desc)
		{
			$label = $desc;
			if (Container::isArray($desc))
			{
				$label = Container::keyValue($desc,
					DateTime::FORMAT_DESCRIPTION_LABEL, $format);
				if (Container::keyExists($desc,
					DateTime::FORMAT_DESCRIPTION_DETAILS))
					$label .= ' (' .
						$desc[DateTime::FORMAT_DESCRIPTION_DETAILS] . ')';
				if (Container::keyExists($desc,
					DateTime::FORMAT_DESCRIPTION_RANGE))
					$label .= ' [' .
						implode('-',
							$desc[DateTime::FORMAT_DESCRIPTION_RANGE]) .
						']';
			}

			$select = $connection->getPlatform()->newStatement(
				SelectQuery::class);

			$select->columns(
				[
					new TimestampFormatFunction($format,
						new CastFunction(
							new Parameter('timestamp', $valueDataType),
							$columnType)),
					'format'
				], new Data($label . ' [' . $format . ']'));

			$validate = true;
			$translation = $connection->getPlatform()->getTimestampFormatTokenTranslation(
				$format);

			if (\is_array($translation)) // Fallback support
			{
				$validate = false;
				$translation = $translation[0];
			}

			if (!\is_string($translation))
				continue;

			$select = ConnectionHelper::prepareStatement($connection,
				$select);

			$this->assertInstanceOf(PreparedStatementInterface::class,
				$select, $dbmsName . ' ' . $method . ' SELECT');

			$this->assertCount(1, $select->getParameters(),
				'Number of parameters of SELECT');

			// Fix case-insensitive filesystem issue
			$formatName = \preg_replace('/([A-Z])/', 'uppercase_$1',
				$format);
			$derivedFilename = $dbmsName . '_' . $formatName;
			$this->assertDerivedFile(\strval($select) . PHP_EOL, $method,
				$derivedFilename, 'sql', $label);

			foreach ($timestamps as $test => $dateTime)
			{
				if (!($dateTime instanceof \DateTimeInterface))
					$dateTime = new DateTime($dateTime,
						DateTIme::getUTCTimezone());
				$expected = $dateTime->format($format);

				$this->queryTest($connection, [
					'format' => $expected
				],
					[
						'select' => [
							$select,
							[
								'timestamp' => $dateTime
							]
						],
						'label' => $dateTime->format(\DateTime::ISO8601) .
						': ' . $dbmsName . ' [' . $format . '] ' . $label,
						'assertValue' => $validate
					]);
			}
		}
	}
}
