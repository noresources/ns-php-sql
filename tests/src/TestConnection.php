<?php
namespace NoreSources\Test;

use NoreSources\Container;
use NoreSources\DataTree;
use NoreSources\TypeDescription;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\ConnectionInterface;
use NoreSources\SQL\Result\InsertionStatementResultInterface;
use NoreSources\SQL\Result\Recordset;
use NoreSources\SQL\Result\RowModificationStatementResultInterface;
use NoreSources\SQL\Statement\StatementDataInterface;

class TestConnection extends \PHPUnit\Framework\TestCase
{

	public function __construct()
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

	public function getAvailableConnectionNames()
	{
		return \array_keys($this->files->getArrayCopy());
	}

	/**
	 *
	 * @param unknown $name
	 * @return \NoreSources\SQL\DBMS\ConnectionInterface
	 */
	public function get($name)
	{
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

	public function getRowValue(ConnectionInterface $connection,
		StatementDataInterface $query, $column, $parameters = array())
	{
		$result = $connection->executeStatement($query, $parameters);
		$this->assertInstanceOf(Recordset::class, $result);
		$row = $result->current();
		if (Container::isArray($row))
			return Container::keyValue($row, $column);
		return null;
	}

	public function queryTest(ConnectionInterface $connection,
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
				$this->assertInstanceOf(
					InsertionStatementResultInterface::class, $result,
					$label . ' - (insert result)');
			elseif ($insert->getStatementType() &
				K::QUERY_FAMILY_ROWMODIFICATION)
				$this->assertInstanceOf(
					RowModificationStatementResultInterface::class,
					$result, $dbmsName . ' (row modification result)');
		}

		if ($select)
		{
			$recordset = $connection->executeStatement($select,
				$selectParameters);
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
				$this->assertCount(1, $recordset);

			$record = $recordset->current();

			$this->assertIsArray($record, $dbmsName . ' valid record');

			if ($assertValue)
				foreach ($expectedValues as $key => $value)
				{
					$this->assertEquals($value, $record[$key],
						$label . ' - record ' . $key . ' value');
				}
		}

		if ($cleanup)
			$connection->executeStatement($cleanup);
	}

	/**
	 *
	 * @var \ArrayObject
	 */
	private $connections;

	/**
	 *
	 * @var \ArrayObject
	 */
	private $files;
}
