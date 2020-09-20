<?php
namespace NoreSources\SQL\DBMS\PDO;

use NoreSources\SQL\DBMS\AbstractPlatform;
use Psr\Log\LoggerAwareTrait;

class PDOPlatform extends AbstractPlatform
{

	use LoggerAwareTrait;

	public function __construct()
	{
		parent::__construct();
	}
}
