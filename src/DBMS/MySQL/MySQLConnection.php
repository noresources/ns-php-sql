<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\MySQL;

use NoreSources\Container;
use NoreSources\TypeConversion;
use NoreSources\TypeDescription;
use NoreSources\SQL\ParameterValue;
use NoreSources\SQL\DBMS\BinaryDataSerializerInterface;
use NoreSources\SQL\DBMS\ConnectionException;
use NoreSources\SQL\DBMS\ConnectionHelper;
use NoreSources\SQL\DBMS\ConnectionInterface;
use NoreSources\SQL\DBMS\StringSerializerInterface;
use NoreSources\SQL\DBMS\TransactionInterface;
use NoreSources\SQL\DBMS\TransactionStackTrait;
use NoreSources\SQL\DBMS\MySQL\MySQLConstants as K;
use NoreSources\SQL\Expression\Literal;
use NoreSources\SQL\Result\GenericInsertionStatementResult;
use NoreSources\SQL\Result\GenericRowModificationStatementResult;
use NoreSources\SQL\Statement\ParameterData;
use NoreSources\SQL\Statement\Statement;
use NoreSources\SQL\Structure\StructureElementInterface;
use NoreSources\SQL\Structure\StructureProviderTrait;

class MySQLConnection implements ConnectionInterface,
	StringSerializerInterface, BinaryDataSerializerInterface,
	TransactionInterface
{

	use StructureProviderTrait;
	use TransactionStackTrait;

	const STATE_CONNECTED = 0x01;

	public function __construct($parameters)
	{
		$this->setTransactionBlockFactory(
			function ($depth, $name) {
				return new MySQLTransactionBlock($this, $name);
			});
		$this->mysqlFlags = 0;
		$this->link = new \mysqli();

		$persistent = Container::keyValue($parameters,
			K::CONNECTION_PERSISTENT, false);
		$protocol = Container::keyValue($parameters,
			K::CONNECTION_PROTOCOL, K::CONNECTION_PROTOCOL_TCP);

		$structure = Container::keyValue($parameters,
			K::CONNECTION_STRUCTURE);
		if ($structure instanceof StructureElementInterface)
			$this->setStructure($structure);

		if ($protocol == K::CONNECTION_PROTOCOL_TCP)
		{
			$host = Container::keyValue($parameters,
				K::CONNECTION_SOURCE, ini_get('mysqli.default_host'));
			$port = Container::keyValue($parameters, K::CONNECTION_PORT,
				ini_get('mysqli.default_port'));
			$user = Container::keyValue($parameters, K::CONNECTION_USER,
				ini_get('mysqli.default_user'));
			$password = Container::keyValue($parameters,
				K::CONNECTION_PASSWORD, ini_get('mysqli.default_pw'));
			$database = Container::keyValue($parameters,
				K::CONNECTION_DEFAULT_NAMESPACE, '');

			if ($persistent && (substr($host, 0, 2) != 'p:'))
				$host = 'p:' . $host;

			$this->link->connect($host, $user, $password, $database,
				$port);
		}
		else // Socket or pipe
		{
			$path = Container::keyValue($parameters,
				K::CONNECTION_SOURCE, ini_get("mysqli.default_socket"));
			if ($persistent && (substr($path, 0, 2) != 'p:'))
				$path = 'p:' . $path;

			$this->link->connect($path);
		}

		/**
		 *
		 * @todo throw on error
		 */

		if ($this->link->connect_errno === 0)
			$this->mysqlFlags |= self::STATE_CONNECTED;
	}

	public function __destruct()
	{
		$this->endTransactions(false);
		if ($this->mysqlFlags & self::STATE_CONNECTED)
			$this->link->close();
	}

	/**
	 *
	 * @return boolean
	 */
	public function isConnected()
	{
		return (($this->mysqlFlags & self::STATE_CONNECTED) ==
			self::STATE_CONNECTED);
	}

	public function quoteStringValue($value)
	{
		return "'" . $this->link->real_escape_string($value) . "'";
	}

	public function quoteBinaryData($value)
	{
		if (\is_integer($value) || \is_float($value) || \is_null($value))
			return $value;
		if ($value instanceof \DateTimeInterface)
			$value = $value->format(
				$this->getPlatform()
					->getTimestampTypeStringFormat(
					K::DATATYPE_TIMESTAMP));
		else
			$value = TypeConversion::toString($value);

		return $this->quoteStringValue($value);
	}

	public function getPlatform()
	{
		if (!isset($this->platform))
		{
			$version = MySQLPlatform::DEFAULT_VERSION;
			if ($this->isConnected())
				$version = $this->getServerLink()->server_version;
			$this->platform = new MySQLPlatform($this, $version);
		}

		return $this->platform;
	}

	public function getStatementBuilder()
	{
		if (!isset($this->builder))
		{
			$this->builder = new MySQLStatementBuilder($this);
		}
		return $this->builder;
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\DBMS\ConnectionInterface::prepareStatement()
	 *
	 * @return MySQLPreparedStatement
	 */
	public function prepareStatement($statement)
	{
		$stmt = $this->link->stmt_init();
		$stmt->prepare(\strval($statement));
		/**
		 *
		 * @todo error management
		 */

		return new MySQLPreparedStatement($stmt, $statement);
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\DBMS\ConnectionInterface::executeStatement()
	 */
	public function executeStatement($statement, $parameters = array())
	{
		if (!($statement instanceof MySQLPreparedStatement ||
			TypeDescription::hasStringRepresentation($statement)))
			throw new ConnectionException($this,
				'Invalid statement type ' .
				TypeDescription::getName($statement) .
				'. Expect PreparedStatement or stringifiable');
		;

		$result = null;
		$statementType = Statement::statementTypeFromData($statement);
		$stmt = null;
		$prepared = ($statement instanceof MySQLPreparedStatement) ? $statement : $this->prepareStatement(
			$statement);
		$stmt = $prepared->getMySQLStmt();

		$result = null;
		if (Container::count($parameters))
		{
			$bindArguments = [];
			$bindArguments[0] = '';
			$map = $prepared->getParameters();
			$values = [];

			foreach ($map as $index => $data)
			{
				$key = $data[ParameterData::KEY];
				$entry = Container::keyValue($parameters, $key, null);
				$bindArguments[0] .= self::getParameterValueTypeKey(
					$entry);
				$values[$index] = ConnectionHelper::serializeParameterValue(
					$this, $entry);

				$bindArguments[] = &$values[$index];
			}

			$result = \call_user_func_array([
				$stmt,
				'bind_param'
			], $bindArguments);

			if ($result === false)
				throw new ConnectionException($this,
					'Failed to bind parameters');
		}

		$result = false;
		$success = @$stmt->execute();
		if ($success)
		{
			/**
			 * Returns a resultset for successful SELECT queries, or FALSE for other DML queries or
			 * on failure.
			 * The mysqli_errno() function can be used to distinguish between the two types of
			 * failure.
			 */
			$result = $stmt->get_result();
		}

		if (!$success || ($result === false && $stmt->errno != 0))
			throw new ConnectionException($this, $stmt->error);

		if ($result instanceof \mysqli_result)
		{
			return new MySQLRecordset($result, $statement);
		}
		elseif ($statementType & K::QUERY_FAMILY_ROWMODIFICATION)
		{
			return new GenericRowModificationStatementResult(
				$stmt->affected_rows);
		}
		elseif ($statementType == K::QUERY_INSERT)
		{
			return new GenericInsertionStatementResult($stmt->insert_id);
		}

		return true;
	}

	public static function dataTypeFromMysqlType($mysqlTypeId)
	{
		switch ($mysqlTypeId)
		{
			case MYSQLI_TYPE_DECIMAL:
			case MYSQLI_TYPE_NEWDECIMAL:
			case MYSQLI_TYPE_FLOAT:
			case MYSQLI_TYPE_DOUBLE:
				return K::DATATYPE_FLOAT;

			case MYSQLI_TYPE_BIT:
				return K::DATATYPE_BOOLE;

			case MYSQLI_TYPE_TINY:
			case MYSQLI_TYPE_SHORT:
			case MYSQLI_TYPE_LONG:
			case MYSQLI_TYPE_LONGLONG:
			case MYSQLI_TYPE_INT24:
				return K::DATATYPE_INTEGER;

			case MYSQLI_TYPE_NULL:
				return K::DATATYPE_NULL;

			case MYSQLI_TYPE_TIMESTAMP:
				return K::DATATYPE_TIMESTAMP;

			case MYSQLI_TYPE_DATE:
			case MYSQLI_TYPE_NEWDATE:
			case MYSQLI_TYPE_YEAR:
				return K::DATATYPE_DATE;

			case MYSQLI_TYPE_TIME:
				return K::DATATYPE_TIME;

			case MYSQLI_TYPE_DATETIME:
				return K::DATATYPE_DATETIME;

			case MYSQLI_TYPE_TINY_BLOB:
			case MYSQLI_TYPE_MEDIUM_BLOB:
			case MYSQLI_TYPE_LONG_BLOB:
			case MYSQLI_TYPE_BLOB:
				return K::DATATYPE_BINARY;
		}

		return K::DATATYPE_STRING;
	}

	/**
	 *
	 * @return \mysqli
	 */
	public function getServerLink()
	{
		return $this->link;
	}

	private static function getParameterValueTypeKey($p)
	{
		$dataType = K::DATATYPE_UNDEFINED;
		if ($p instanceof ParameterValue)
			$dataType = $p->type;
		else
			$dataType = Literal::dataTypeFromValue($p);

		if ($dataType == K::DATATYPE_INTEGER)
			return 'i';
		elseif ($dataType & K::DATATYPE_FLOAT)
			return 'd';
		elseif ($dataType == K::DATATYPE_BINARY)
			return 'b';

		return 's';
	}

	/**
	 *
	 * @var MySQLStatementBuilder
	 */
	private $builder;

	/**
	 *
	 * @var \NoreSources\SQL\Statement\StatementFactoryInterface
	 */
	private $statementFactory;

	/**
	 *
	 * @var \mysqli
	 */
	private $link;

	/**
	 *
	 * @var integer
	 */
	private $mysqlFlags;
}