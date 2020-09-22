<?php
namespace NoreSources\SQL\DBMS\Reference;

use NoreSources\Container;
use NoreSources\SQL\DBMS\AbstractPlatform;
use NoreSources\SQL\DBMS\TypeHelper;
use NoreSources\SQL\Structure\ColumnDescriptionInterface;
use Psr\Log\LoggerAwareTrait;

class ReferencePlatform extends AbstractPlatform
{

	use LoggerAwareTrait;

	const DEFAULT_VERSION = '1.0.0';

	public function __construct($features = array())
	{
		parent::__construct(self::DEFAULT_VERSION);
		foreach ($features as $feature)
			$this->setPlatformFeature($feature[0], $feature[1]);
	}

	function getColumnType(ColumnDescriptionInterface $column,
		$constraintFlags = 0)
	{
		return Container::firstValue(
			TypeHelper::getMatchingTypes($column,
				StandardTypeRegistry::getInstance()));
	}

	public function setReferencePlatformVersion($kind, $version)
	{
		return $this->setPlatformVersion($kind, $version);
	}
}