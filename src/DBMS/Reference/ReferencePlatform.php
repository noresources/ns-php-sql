<?php
namespace NoreSources\SQL\DBMS\Reference;

use NoreSources\Container;
use NoreSources\SQL\DBMS\AbstractPlatform;
use NoreSources\SQL\DBMS\TypeHelper;
use NoreSources\SQL\Statement\ParameterData;
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

	public function quoteStringValue($value)
	{
		return "'" . \str_replace("'", "''", $value) . "'";
	}

	public function quoteBinaryData($value)
	{
		return $this->quoteStringValue($value);
	}

	public function quoteIdentifier($identifier)
	{
		return '[' . $identifier . ']';
	}

	function getColumnType(ColumnDescriptionInterface $column,
		$constraintFlags = 0)
	{
		return Container::firstValue(
			TypeHelper::getMatchingTypes($column,
				StandardTypeRegistry::getInstance()));
	}

	public function getParameter($name, ParameterData $parameters = null)
	{
		return ('$' . preg_replace('/[^a-zA-Z0-9_]/', '_', $name));
	}

	public function setReferencePlatformVersion($kind, $version)
	{
		return $this->setPlatformVersion($kind, $version);
	}
}