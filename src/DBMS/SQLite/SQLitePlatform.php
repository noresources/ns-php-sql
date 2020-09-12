<?php
namespace NoreSources\SQL\DBMS\SQLite;

use NoreSources\SQL\DBMS\AbstractPlatform;

class SQLitePlatform extends AbstractPlatform
{

	public function __construct($version)
	{
		parent::__construct($version);

		$this->setPlatformFeature(
			[
				self::FEATURE_INSERT,
				self::FEATURE_DEFAULT
			], false);
		$this->setPlatformFeature(
			[
				self::FEATURE_INSERT,
				self::FEATURE_DEFAULTVALUES
			], true);
		$this->setPlatformFeature(
			[
				self::FEATURE_SELECT,
				self::FEATURE_EXTENDED_RESULTCOLUMN_RESOLUTION
			], true);
		$this->setPlatformFeature([
			self::FEATURE_SCOPED
		], true);
	}
}
