<?php
namespace NoreSources\Test;

use NoreSources\DataTree;
use NoreSources\TypeDescription;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\Connection;
use NoreSources\SQL\DBMS\ConnectionHelper;
use NoreSources\SQL\DBMS\PreparedStatement;
use NoreSources\SQL\QueryResult\InsertionQueryResult;
use NoreSources\SQL\QueryResult\Recordset;
use NoreSources\SQL\QueryResult\RowModificationQueryResult;
use NoreSources\SQL\Statement\StatementData;

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

	public function get($name)
	{
		if ($this->connections->offsetExists($name))
			return $this->connections[$name];

		if (!$this->files->offsetExists($name))
			return null;

		$parameters = new DataTree();
		$parameters->load($this->files[$name]);

		$this->connections[$name] = ConnectionHelper::createConnection($parameters);
		return $this->connections[$name];
	}

	public function queryTest(Connection $connection, PreparedStatement $insert, $parameters,
		$expectedValues, StatementData $select = null, StatementData $cleanup = null)
	{
		$dbmsName = TypeDescription::getLocalName($connection);
		$result = $connection->executeStatement($insert, $parameters);
		if ($insert->getStatementType() & K::QUERY_INSERT)
			$this->assertInstanceOf(InsertionQueryResult::class, $result);
		elseif ($insert->getStatementType() & K::QUERY_FAMILY_ROWMODIFICATION)
			$this->assertInstanceOf(RowModificationQueryResult::class, $result);

		if ($select)
		{
			$recordset = $connection->executeStatement($select);
			$this->assertInstanceOf(Recordset::class, $recordset);

			/**
			 *
			 * @var Recordset $recordset
			 */

			$recordset->setFlags($recordset->getFlags() | Recordset::FETCH_UNSERIALIZE);

			if ($recordset instanceof \Countable)
				$this->assertCount(1, $recordset);

			$record = $recordset->current();

			$this->assertIsArray($record, $dbmsName . ' valid record');

			foreach ($expectedValues as $key => $value)
			{
				$this->assertEquals($value, $record[$key], $dbmsName . ' record ' . $key . ' value');
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
