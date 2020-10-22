<?php
namespace NoreSources\SQL;

use NoreSources\SQL\DBMS\MySQL\MySQLConnection;
use NoreSources\SQL\DBMS\PostgreSQL\PostgreSQLConstants as K;

final class MySQLTest extends \PHPUnit\Framework\TestCase
{

	public function testInvalidConnection()
	{
		$this->expectException(\RuntimeException::class);
		$env = new Environment(
			[
				K::CONNECTION_TYPE => MySQLConnection::class,
				K::CONNECTION_SOURCE => 'void.null.twisting-neither.shadow',
				K::CONNECTION_PORT => 0,
				K::CONNECTION_USER => 'Xul',
				K::CONNECTION_PASSWORD => 'keymaster.and.cerberus'
			]);
	}
}
