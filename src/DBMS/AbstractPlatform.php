<?php
namespace NoreSources\SQL\DBMS;

use NoreSources\CascadedValueTree;
use NoreSources\Container;
use NoreSources\SemanticVersion;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Expression\FunctionCall;
use NoreSources\SQL\Expression\MetaFunctionCall;

abstract class AbstractPlatform implements PlatformInterface
{

	const DEFAULT_VERSION = '0.0.0';

	public function getPlatformVersion($kind = self::VERSION_CURRENT)
	{
		return Container::keyValue($this->versions, $kind, false);
	}

	public function getKeyword($keyword)
	{
		switch ($keyword)
		{
			case K::KEYWORD_AUTOINCREMENT:
				return 'AUTO INCREMENT';
			case K::KEYWORD_CURRENT_TIMESTAMP:
				return 'CURRENT_TIMESTAMP';
			case K::KEYWORD_NULL:
				return 'NULL';
			case K::KEYWORD_TRUE:
				return 'TRUE';
			case K::KEYWORD_FALSE:
				return 'FALSE';
			case K::KEYWORD_DEFAULT:
				return 'DEFAULT';
		}

		throw new \InvalidArgumentException(
			'Keyword ' . $keyword . ' is not available');
	}

	public function getJoinOperator($joinTypeFlags)
	{
		$s = '';
		if (($joinTypeFlags & K::JOIN_NATURAL) == K::JOIN_NATURAL)
			$s .= 'NATURAL ';

		if (($joinTypeFlags & K::JOIN_LEFT) == K::JOIN_LEFT)
		{
			$s . 'LEFT ';
			if (($joinTypeFlags & K::JOIN_OUTER) == K::JOIN_OUTER)
			{
				$s .= 'OUTER ';
			}
		}
		elseif (($joinTypeFlags & K::JOIN_RIGHT) == K::JOIN_RIGHT)
		{
			$s . 'RIGHT ';
			if (($joinTypeFlags & K::JOIN_OUTER) == K::JOIN_OUTER)
			{
				$s .= 'OUTER ';
			}
		}
		elseif (($joinTypeFlags & K::JOIN_CROSS) == K::JOIN_CROSS)
		{
			$s .= 'CROSS ';
		}
		elseif (($joinTypeFlags & K::JOIN_INNER) == K::JOIN_INNER)
		{
			$s .= 'INNER ';
		}

		return ($s . 'JOIN');
	}

	public function getForeignKeyAction($action)
	{
		switch ($action)
		{
			case K::FOREIGN_KEY_ACTION_CASCADE:
				return 'CASCADE';
			case K::FOREIGN_KEY_ACTION_RESTRICT:
				return 'RESTRICT';
			case K::FOREIGN_KEY_ACTION_SET_DEFAULT:
				return 'SET DEFAULT';
			case K::FOREIGN_KEY_ACTION_SET_NULL:
				'SET NULL';
		}
		return 'NO ACTION';
	}

	public function getTimestampTypeStringFormat($type = 0)
	{
		switch ($type)
		{
			case K::DATATYPE_DATE:
				return 'Y-m-d';
			case K::DATATYPE_TIME:
				return 'H:i:s';
			case K::DATATYPE_TIMEZONE:
				return 'H:i:sO';
			case K::DATATYPE_DATETIME:
				return 'Y-m-d\TH:i:s';
		}

		return \DateTimeInterface::ISO8601;
	}

	public function getTimestampFormatTokenTranslation($formatToken)
	{
		return false;
	}

	/**
	 * Translate a portable function to the DBMS equivalent
	 *
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\DBMS\PlatformInterface::translateFunction()
	 */
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
