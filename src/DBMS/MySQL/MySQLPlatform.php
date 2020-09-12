<?php
namespace NoreSources\SQL\DBMS\MySQL;

use NoreSources\SQL\DBMS\AbstractPlatform;

class MySQLPlatform extends AbstractPlatform
{

	const DEFAULT_VERSION = '4.0.0';

	public function __construct($version)
	{
		parent::__construct($version);
		$this->setPlatformFeature(
			[
				self::FEATURE_INSERT,
				self::FEATURE_DEFAULT
			], true);

		$this->setPlatformFeature(
			[
				self::FEATURE_CREATE,
				self::FEATURE_TABLE,
				self::FEATURE_REPLACE
			], true);
		$this->setPlatformFeature(
			[
				self::FEATURE_CREATE,
				self::FEATURE_TABLE,
				self::FEATURE_TEMPORARY
			], true);

		$this->setPlatformFeature(
			[
				self::FEATURE_CREATE,
				self::FEATURE_TABLE,
				self::FEATURE_COLUMN_DECLARATION_FLAGS
			],
			(self::FEATURE_COLUMN_ENUM |
			self::FEATURE_COLUMN_KEY_MANDATORY_LENGTH));
	}
}