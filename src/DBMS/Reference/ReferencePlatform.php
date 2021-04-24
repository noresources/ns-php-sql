<?php
namespace NoreSources\SQL\DBMS\Reference;

use NoreSources\Container;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\AbstractPlatform;
use NoreSources\SQL\DBMS\TypeRegistry;
use NoreSources\SQL\Syntax\Statement\ParameterData;
use Psr\Log\LoggerAwareTrait;

class ReferencePlatform extends AbstractPlatform
{
	use LoggerAwareTrait;

	const DEFAULT_VERSION = '1.0.0';

	public function __construct($parameters = array(),
		$features = array())
	{
		parent::__construct(
			\array_merge(
				[
					self::VERSION_CURRENT => self::DEFAULT_VERSION
				], $parameters));
		$this->initializeStatementFactory();
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

	function getColumnType($columnDescription, $constraintFlags = 0)
	{

		/**
		 *
		 * @var TypeRegistry $registry
		 */
		$registry = StandardTypeRegistry::getInstance();

		return Container::firstValue(
			$registry->matchDescription($columnDescription));
	}

	public function getTypeRegistry()
	{
		return StandardTypeRegistry::getInstance();
	}

	public function getParameter($name, ParameterData $parameters = null)
	{
		return ('$' . preg_replace('/[^a-zA-Z0-9_]/', '_', $name));
	}

	public function getKeyword($keyword)
	{
		switch ($keyword)
		{
			case K::KEYWORD_NAMESPACE:
				return 'NAMESPACE';
		}

		return parent::getKeyword($keyword);
	}

	public function setReferencePlatformVersion($kind, $version)
	{
		return $this->setPlatformVersion($kind, $version);
	}
}