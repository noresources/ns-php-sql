<?php
namespace NoreSources\Test;

use NoreSources\DateTime;
use NoreSources\DateTimeZone;
use NoreSources\SemanticVersion;
use NoreSources\Container\Container;
use NoreSources\Container\DataTree;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\ConnectionException;
use NoreSources\SQL\DBMS\ConnectionInterface;
use NoreSources\SQL\DBMS\PlatformInterface;
use NoreSources\SQL\DBMS\Configuration\ConfiguratorInterface;
use NoreSources\SQL\DBMS\PDO\PDOPlatform;
use NoreSources\SQL\Result\InsertionStatementResultInterface;
use NoreSources\SQL\Result\Recordset;
use NoreSources\SQL\Result\RowModificationStatementResultInterface;
use NoreSources\SQL\Structure\NamespaceStructure;
use NoreSources\SQL\Structure\TableStructure;
use NoreSources\SQL\Syntax\Statement\StatementDataInterface;
use NoreSources\SQL\Syntax\Statement\Structure\CreateIndexQuery;
use NoreSources\SQL\Syntax\Statement\Structure\CreateNamespaceQuery;
use NoreSources\SQL\Syntax\Statement\Structure\CreateTableQuery;
use NoreSources\SQL\Syntax\Statement\Structure\DropIndexQuery;
use NoreSources\SQL\Syntax\Statement\Structure\DropTableQuery;
use NoreSources\Type\TypeConversion;
use NoreSources\Type\TypeDescription;
use PHPUnit\Framework\TestCase;

trait UnittestConnectionManagerTrait
{

	protected function getAvailableConnectionNames()
	{
		if (!isset($this->connections))
			$this->initializeConnections();
		return \array_keys($this->files->getArrayCopy());
	}

	/*
	 *
	 * @param unknown $name
	 * @return \NoreSources\SQL\DBMS\ConnectionInterface
	 */
	protected function getConnection($name)
	{
		if (!isset($this->connections))
			$this->initializeConnections();
		if ($this->connections->offsetExists($name))
			return $this->connections[$name];

		if ($this->files->offsetExists($name))
		{
			$parameters = new DataTree();
			$parameters->loadFile($this->files[$name]);
		}
		else
			$parameters = [
				K::CONNECTION_TYPE => $name
			];

		$this->connections[$name] = ConnectionHelper::createConnection(
			$parameters);
		return $this->connections[$name];
	}

	protected function getRowValue(ConnectionInterface $connection,
		StatementDataInterface $query, $column, $parameters = array())
	{
		$result = $connection->executeStatement($query, $parameters);
		if ($this instanceof TestCase)
			$this->assertInstanceOf(Recordset::class, $result);
		$row = $result->current();
		if (Container::isArray($row))
			return Container::keyValue($row, $column);
		return null;
	}

	protected function getDBMSName(ConnectionInterface $connection)
	{
		$dbmsName = \preg_replace('/Connection/', '',
			TypeDescription::getLocalName($connection));

		$platform = $connection->getPlatform();

		if ($platform instanceof PDOPlatform)
		{
			$base = $platform->getBasePlatform();

			$baseName = \preg_replace('/Platform$/', '',
				TypeDescription::getLocalName($base));

			$dbmsName .= '_' . $baseName;
		}

		$version = $platform->getPlatformVersion(
			K::PLATFORM_VERSION_COMPATIBILITY);
		$versionString = $version->slice(SemanticVersion::MAJOR,
			SemanticVersion::MINOR);

		$dbmsName .= '_' . $versionString;

		return $dbmsName;
	}

	protected function getMethodName($backLevel = 2)
	{
		return __CLASS__ . '::' .
			debug_backtrace()[$backLevel]['function'];
	}

	protected function isPlatform(PlatformInterface $platform,
		$classname)
	{
		if ($platform instanceof PDOPlatform)
			$platform = $platform->getBasePlatform();
		return \strcmp(TypeDescription::getName($platform), $classname) ==
			0;
	}

	protected function setTimezone(ConnectionInterface $connection,
		$timezone, $verbose = false)
	{
		$now = new DateTime('now', DateTime::getUTCTimezone());
		if (!($timezone instanceof \DateTimeZone))
			$timezone = DateTimeZone::createFromDescription($timezone);

		$offset = $timezone->getOffset($now);

		/** @var ConfiguratorInterface $configurator */
		$configurator = $connection->getConfigurator();
		$platform = $connection->getPlatform();
		if ($verbose)
			echo ('Set ' . $this->getDBMSName($connection) .
				' time zone ' . TypeConversion::toString($timezone) .
				' (' . $offset . ')' . PHP_EOL);

		if ($configurator->canSet(K::CONFIGURATION_TIMEZONE))
		{
			$configurator->offsetSet(K::CONFIGURATION_TIMEZONE,
				$timezone);

			if ($configurator->canGet(K::CONFIGURATION_TIMEZONE))
			{
				$postSetTimezone = $configurator->get(
					K::CONFIGURATION_TIMEZONE);
				if (!($postSetTimezone instanceof \DateTimeZone))
					throw new \Exception(
						'Failed to get timezone from connection');
				$postSetOffset = $postSetTimezone->getOffset($now);

				if ($verbose)
					echo ('New ' . $this->getDBMSName($connection) .
						' time zone ' .
						TypeConversion::toString($postSetTimezone) . ' (' .
						$postSetOffset . ')' . PHP_EOL);

				if ($postSetOffset != $offset)
					throw new \Exception(
						'Failed to set correct time zone offset. Expect ' .
						TypeConversion::toString($timezone) . ' (' .
						$offset . '), got ' .
						TypeConversion::toString($postSetTimezone) . ' (' .
						$postSetOffset . ')');
			}
			else
				\trigger_error(
					'Unable to get ' . $this->getDBMSName($connection) .
					' time zone', E_USER_NOTICE);
		}
	}

	protected function queryTest(ConnectionInterface $connection,
		$expectedValues, $options = array())
	{
		$dbmsName = TypeDescription::getLocalName($connection);
		$insertParameters = array();
		$insert = Container::keyValue($options, 'insert', null);
		$assertValue = Container::keyValue($options, 'assertValue', true);
		if (\is_array($insert))
		{
			$insertParameters = $insert[1];
			$insert = $insert[0];
		}

		$selectParameters = array();
		$select = Container::keyValue($options, 'select', null);
		if (\is_array($select))
		{
			$selectParameters = $select[1];
			$select = $select[0];
		}
		$cleanup = Container::keyValue($options, 'cleanup', null);
		$label = Container::keyValue($options, 'label', $dbmsName);

		if ($insert instanceof StatementDataInterface)
		{
			$result = $connection->executeStatement($insert,
				$insertParameters);
			if ($insert->getStatementType() & K::QUERY_INSERT)
			{
				if ($this instanceof TestCase)
					$this->assertInstanceOf(
						InsertionStatementResultInterface::class,
						$result, $label . ' - (insert result)');
			}
			elseif ($insert->getStatementType() &
				K::QUERY_FAMILY_ROWMODIFICATION)
			{
				if ($this instanceof TestCase)
					$this->assertInstanceOf(
						RowModificationStatementResultInterface::class,
						$result,
						$dbmsName . ' (row modification result)');
			}
		}

		if ($select)
		{
			$recordset = $connection->executeStatement($select,
				$selectParameters);
			if ($this instanceof TestCase)
				$this->assertInstanceOf(Recordset::class, $recordset,
					$label . ' - (select result)');

			/**
			 *
			 * @var Recordset $recordset
			 */

			$recordset->setFlags(
				Recordset::FETCH_ASSOCIATIVE |
				Recordset::FETCH_UNSERIALIZE);

			if ($recordset instanceof \Countable)
			{
				if ($this instanceof TestCase)
					$this->assertCount(1, $recordset);
			}

			$record = $recordset->current();

			if ($this instanceof TestCase)
				$this->assertTrue(\is_array($record),
					$dbmsName . ' valid record');

			if ($assertValue)
				foreach ($expectedValues as $key => $value)
				{
					if ($this instanceof TestCase)
						$this->assertEquals($value, $record[$key],
							$label . ' - [' . $key .
							'] result column value');
				}
		}

		if ($cleanup)
			$connection->executeStatement($cleanup);
	}

	protected function recreateTable(ConnectionInterface $connection,
		TableStructure $tableStructure, $method = null, $save = true,
		$suffix = '')
	{
		$hasAssertDerivedFile = \method_exists($this,
			'assertDerivedFile');

		$dbmsName = $this->getDBMSName($connection);
		$method = ($method ? $method : $this->getMethodName(2));

		$platform = $connection->getPlatform();
		$factory = $connection->getPlatform();

		$parent = $tableStructure->getParentElement();
		if ($parent instanceof NamespaceStructure)
		{
			/**
			 *
			 * @var CreateNamespaceQuery
			 */
			$createNamespace = $factory->newStatement(
				CreateNamespaceQuery::class);

			try
			{
				if (!($createNamespace instanceof CreateNamespaceQuery))
					throw new \Exception(
						'not CREATE NAMESPACE query available');
				$createNamespace->identifier($parent->getName());
				$createNamespace->createFlags(
					K::FEATURE_CREATE_EXISTS_CONDITION);

				$data = ConnectionHelper::buildStatement($connection,
					$createNamespace, $parent);
				$connection->executeStatement($data);
			}
			catch (ConnectionException $e)
			{}
		}

		// Drop indexes
		$constraints = $tableStructure->getConstraints();
		foreach ($constraints as $id => $constraint)
		{
			if (!($constraint instanceof IndexTableConstraint))
				continue;

			/**
			 *
			 * @var IndexTableConstraint $constraint
			 */

			$name = ($constraint->getName() ? $constraint->getName() : $id);

			$dropIndex = $platform->newStatement(DropIndexQuery::class);
			if ($dropIndex instanceof DropIndexQuery)
			{
				$dropIndex->dropFlags(K::DROP_EXISTS_CONDITION);
				$dropIndex->identifier($constraint->getName());
				$data = ConnectionHelper::buildStatement($connection,
					$dropIndex, $tableStructure);
				$sql = \SqlFormatter::format(strval($data), false) .
					PHP_EOL;
				if ($save && $hasAssertDerivedFile)
					$this->assertDerivedFile($sql, $method,
						$dbmsName . $suffix . '_dropindex_' .
						$tableStructure->getName() . "_" . $name, 'sql');

				try
				{
					$connection->executeStatement($data);
				}
				catch (ConnectionException $e)
				{}
			}
		}

		$platformDropFlags = $platform->queryFeature(
			[
				K::FEATURE_DROP,
				K::FEATURE_ELEMENT_TABLE,
				K::FEATURE_DROP_FLAGS
			], 0);

		try // PostgreSQL < 8.2 does not support DROP IF EXISTS and may fail
		{
			$drop = $connection->getPlatform()->newStatement(
				DropTableQuery::class);
			if ($drop instanceof DropTableQuery)
				$drop->dropFlags(
					K::DROP_CASCADE | K::DROP_EXISTS_CONDITION)->forStructure(
					$tableStructure);
			$data = ConnectionHelper::buildStatement($connection, $drop,
				$tableStructure);
			$connection->executeStatement($data);
		}
		catch (ConnectionException $e)
		{
			if (($platformDropFlags & K::FEATURE_DROP_EXISTS_CONDITION))
				throw $e;
		}

		/**
		 *
		 * @var CreateTableQuery $createTable
		 */
		$createTable = $factory->newStatement(CreateTableQuery::class,
			$tableStructure);

		$this->assertInstanceOf(CreateTableQuery::class, $createTable,
			$dbmsName . ' CreateTableQuery');
		$this->assertInstanceOf(TableStructure::class,
			$createTable->getStructure(),
			$dbmsName . ' CrateTableQuery table reference');

		$createTable->createFlags(
			$createTable->getCreateFlags() | CreateTableQuery::REPLACE |
			K::CREATE_EXISTS_CONDITION);
		$result = false;
		$data = ConnectionHelper::buildStatement($connection,
			$createTable, $tableStructure);
		$sql = \SqlFormatter::format(strval($data), false);
		if ($save && $hasAssertDerivedFile)
			$this->assertDerivedFile($sql, $method,
				$dbmsName . $suffix . '_create_' .
				$tableStructure->getName(), 'sql');

		try
		{
			$result = $connection->executeStatement($data);
		}
		catch (\Exception $e)
		{
			$this->assertEquals(true, $result,
				'Create table ' . $tableStructure->getName() . ' on ' .
				TypeDescription::getLocalName($connection) . PHP_EOL .
				\strval($data) . ': ' . $e->getMessage());
		}

		$this->assertTrue($result,
			'Create table ' . $tableStructure->getName() . ' on ' .
			TypeDescription::getLocalName($connection));

		/**
		 *
		 * @todo Find IndexStructure instead
		 */
		if (false)
		{
			$constraints = $tableStructure->getConstraints();
			foreach ($constraints as $id => $constraint)
			{
				if (!($constraint instanceof IndexTableConstraint))
					continue;
				/**
				 *
				 * @var IndexTableConstraint $constraint
				 */

				$name = ($constraint->getName() ? $constraint->getName() : $id);

				$createIndex = $platform->newStatement(
					CreateIndexQuery::class);
				if ($createIndex instanceof CreateIndexQuery)
				{
					$createIndex->setFromTable($tableStructure,
						$constraint->getName());
					$data = ConnectionHelper::buildStatement(
						$connection, $createIndex, $tableStructure);
					$sql = \SqlFormatter::format(strval($data), false) .
						PHP_EOL;
					if ($save && $hasAssertDerivedFile)
						$this->assertDerivedFile($sql, $method,
							$dbmsName . $suffix . '_createindex_' .
							$tableStructure->getName() . '_' . $name,
							'sql');
				}

				try
				{
					$connection->executeStatement($data);
				}
				catch (ConnectionException $e)
				{}
			}
		}

		return $result;
	}

	protected function runConnectionTest($method, $validator = null,
		$args = array())
	{
		$settings = $this->getAvailableConnectionNames();
		$subMethod = \preg_replace('/^(.*?)::test(.*)/', '\1::dbms\2',
			$method);
		$count = 0;

		foreach ($settings as $dbmsName)
		{
			$connection = $this->getConnection($dbmsName);
			if (\is_callable($validator) &&
				!\call_user_func($validator, $connection))
				continue;

			$count++;
			$dbmsName = $this->getDBMSName($connection);

			$arguments = \array_merge(
				[
					$connection,
					$dbmsName,
					$method
				], $args);
			call_user_func_array([
				$this,
				$subMethod
			], $arguments);
		}

		if ($count == 0)
			$this->assertTrue(true, 'No-op');
	}

	protected function initializeConnections()
	{
		$this->connections = new \ArrayObject();
		$this->files = new \ArrayObject();

		$basePath = __DIR__ . '/../settings';
		if (\is_dir($basePath))
		{
			$basePath = realpath($basePath);
			$iterator = opendir($basePath);
			while ($item = readdir($iterator))
			{
				$path = $basePath . '/' . $item;
				if (\is_file($path) && (\preg_match('/\.php$/', $path)) &&
					(\strpos($item, '.example.php') === false))
				{
					$key = pathinfo($path, PATHINFO_FILENAME);
					$this->files[$key] = $path;
				}
			}
			closedir($iterator);
		}
	}

	/**
	 *
	 * @var ConnectionInterface[]
	 */
	private $connections;

	/**
	 *
	 * @var \ArrayObject
	 */
	private $files;
}
