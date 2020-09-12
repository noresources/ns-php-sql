<?php
namespace NoreSources\SQL\DBMS\PostgreSQL;

use NoreSources\SemanticVersion;
use NoreSources\SQL\DBMS\AbstractPlatform;

class PostgreSQLPlatform extends AbstractPlatform
{

	/**
	 *
	 * @var string
	 */
	const DEFAULT_VERSION = '7.3.0';

	public function __construct($version = self::DEFAULT_VERSION)
	{
		parent::__construct($version);

		$this->setPlatformFeature(
			[
				self::FEATURE_INSERT,
				self::FEATURE_DEFAULT
			], true);
		$this->setPlatformFeature(
			[
				self::FEATURE_INSERT,
				self::FEATURE_DEFAULTVALUES
			], true);

		$serverVersion = $this->getPlatformVersion();
		$compatibility = $serverVersion->slice(SemanticVersion::MAJOR,
			SemanticVersion::MAJOR);

		if (SemanticVersion::compareVersions($serverVersion, '7.3.0') >=
			0)
		{
			$this->setPlatformFeature(
				[
					self::FEATURE_DROP,
					self::FEATURE_CASCADE
				], false);

			$compatibility = '7.3.0';
		}

		if (SemanticVersion::compareVersions($serverVersion, '8.2.0') >=
			0)
		{
			$this->setPlatformFeature(
				[
					self::FEATURE_DROP,
					self::FEATURE_EXISTS_CONDITION
				], true);

			$compatibility = '8.2.0';
		}

		if (SemanticVersion::compareVersions($serverVersion, '9.1.0') >=
			0)
		{
			$this->setPlatformFeature(
				[
					self::FEATURE_CREATE,
					self::FEATURE_TABLE,
					self::FEATURE_EXISTS_CONDITION
				], true);

			$compatibility = '9.1.0';
		}

		if (SemanticVersion::compareVersions($serverVersion, '9.3.0') >=
			0)
		{
			$this->setPlatformFeature(
				[
					self::FEATURE_CREATE,
					self::FEATURE_NAMESPACE,
					self::FEATURE_EXISTS_CONDITION
				], true);

			$compatibility = '9.3.0';
		}

		if (SemanticVersion::compareVersions($serverVersion, '10.0.0') >=
			0)
		{
			$compatibility = '10.0.0';
		}

		$compatibility = ($compatibility instanceof SemanticVersion) ? $compatibility : new SemanticVersion(
			$compatibility);
		$this->setPlatformVersion(self::VERSION_COMPATIBILITY,
			$compatibility);
	}
}

