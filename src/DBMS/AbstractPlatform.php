<?php
namespace NoreSources\SQL\DBMS;

use NoreSources\CascadedValueTree;
use NoreSources\Container;
use NoreSources\SemanticVersion;
use NoreSources\SQL\Expression\FunctionCall;
use NoreSources\SQL\Expression\MetaFunctionCall;

class AbstractPlatform implements PlatformInterface
{

	const DEFAULT_VERSION = '0.0.0';

	public function getPlatformVersion($kind = self::VERSION_CURRENT)
	{
		return Container::keyValue($this->versions, $kind, false);
	}

	function translateFunction(MetaFunctionCall $meta)
	{
		return new FunctionCall($meta->getFunctionName(),
			$meta->getArguments());
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\DBMS\PlatformInterface::queryFeature()
	 */
	public function queryFeature($query, $dflt = null)
	{
		return $this->features->query($query,
			($dflt === null) ? true : $dflt);
	}

	protected function __construct($version = self::DEFAULT_VERSION)
	{
		$version = new SemanticVersion($version);
		$this->versions = [
			self::VERSION_CURRENT => $version,
			self::VERSION_COMPATIBILITY => new SemanticVersion(
				$version->slice(SemanticVersion::MAJOR,
					SemanticVersion::MAJOR))
		];

		$this->features = new CascadedValueTree();

		$this->features[self::FEATURE_JOINS] = 0xFFFF;
		$this->features[self::FEATURE_EVENT_ACTIONS] = 0xFFFF;
	}

	protected function setPlatformVersion($kind, $version)
	{
		$this->versions[$kind] = ($version instanceof SemanticVersion) ? $version : new SemanticVersion(
			$version);

		$delta = SemanticVersion::compareVersions(
			$this->versions[self::VERSION_COMPATIBILITY],
			$this->versions[self::VERSION_CURRENT]);

		if ($delta <= 0)
			return;

		if ($kind == self::VERSION_COMPATIBILITY)
			$this->versions[self::VERSION_CURRENT] = $this->versions[self::VERSION_COMPATIBILITY];
		else
			$this->versions[self::VERSION_COMPATIBILITY] = $this->versions[self::VERSION_CURRENT];
	}

	protected function setPlatformFeature($query, $value)
	{
		$this->features->offsetSet($query, $value);
	}

	/**
	 *
	 * @var SemanticVersion[]
	 */
	private $versions;

	/**
	 *
	 * @var CascadedValueTree
	 */
	private $features;
}
