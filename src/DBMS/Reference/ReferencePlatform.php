<?php
namespace NoreSources\SQL\DBMS\Reference;

use NoreSources\SQL\DBMS\AbstractPlatform;
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

	public function setReferencePlatformVersion($kind, $version)
	{
		return $this->setPlatformVersion($kind, $version);
	}
}