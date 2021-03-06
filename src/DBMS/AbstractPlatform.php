<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS;

use NoreSources\CascadedValueTree;
use NoreSources\Container;
use NoreSources\DateTime;
use NoreSources\SemanticVersion;
use NoreSources\TypeConversion;
use NoreSources\TypeDescription;
use NoreSources\Expression\ExpressionInterface;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DataTypeProviderInterface;
use NoreSources\SQL\MediaTypeUtility;
use NoreSources\SQL\Structure\DatasourceStructure;
use NoreSources\SQL\Structure\StructureElementIdentifier;
use NoreSources\SQL\Structure\StructureElementInterface;
use NoreSources\SQL\Syntax\Evaluator;
use NoreSources\SQL\Syntax\FunctionCall;
use NoreSources\SQL\Syntax\MetaFunctionCall;
use NoreSources\SQL\Syntax\Statement\Traits\ClassMapStatementFactoryTrait;

/**
 * Reference PlatformInterface implementation
 */
abstract class AbstractPlatform implements PlatformInterface
{

	use ClassMapStatementFactoryTrait;

	const DEFAULT_VERSION = '0.0.0';

	public function literalize($value, $dataType = null)
	{
		if ($dataType === null)
			$dataType = Evaluator::getInstance()->getDataType($value);

		if ($dataType == K::DATATYPE_NULL)
			return null;
		$dataType &= ~K::DATATYPE_NULL;

		switch ($dataType)
		{
			case K::DATATYPE_BOOLEAN:
				return TypeConversion::toBoolean($value);
			case K::DATATYPE_STRING:
				return TypeCOnversion::toString($value);
			case K::DATATYPE_INTEGER:
				return TypeConversion::toInteger($value);
			case K::DATATYPE_FLOAT:
			case K::DATATYPE_NUMBER:
				return TypeConversion::toFloat($value);
			case K::DATATYPE_DATE:
			case K::DATATYPE_DATETIME:
			case K::DATATYPE_TIME:
			case K::DATATYPE_TIMEZONE:
			case K::DATATYPE_TIMESTAMP:

				if (!($value instanceof \DateTimeInterface))
				{
					try
					{
						$value = new DateTime($value);
					}
					catch (\Exception $e)
					{}
				}

				if ($value instanceof \DateTimeInterface)
				{
					return $value->format(
						$this->getTimestampTypeStringFormat($dataType));
				}
		}

		return TypeConversion::toString($value);
	}

	function serializeData($data, $dataType)
	{
		if ($dataType == K::DATATYPE_NULL)
			return $this->getKeyword(K::KEYWORD_NULL);
		$dataType &= ~K::DATATYPE_NULL;

		switch ($dataType)
		{
			case K::DATATYPE_BINARY:
				return $this->quoteBinaryData($data);
			case K::DATATYPE_BOOLEAN:
				return $this->getKeyword(
					TypeConversion::toBoolean($data) ? K::KEYWORD_TRUE : K::KEYWORD_FALSE);
			case K::DATATYPE_INTEGER:
				return TypeConversion::toInteger($data);
			case K::DATATYPE_FLOAT:
			case K::DATATYPE_NUMBER:
				return TypeCOnversion::toFloat($data);
		}

		return $this->quoteStringValue(
			$this->literalize($data, $dataType));
	}

	public function serializeColumnData($columnDescription, $data)
	{
		if (($mediaType = Container::keyValue($columnDescription,
			K::COLUMN_MEDIA_TYPE)))
		{
			$mediaType = $columnDescription->get(K::COLUMN_MEDIA_TYPE);
			$data = MediaTypeUtility::toString($data, $mediaType);
		}

		$dataType = K::DATATYPE_UNDEFINED;
		if ($columnDescription instanceof DataTypeProviderInterface)
			$dataType = $columnDescription->getDataType();
		else
			$dataType = Container::keyValue($columnDescription,
				K::COLUMN_DATA_TYPE, $dataType);

		return $this->serializeData($data, $dataType);
	}

	public function quoteStringValue($value)
	{
		if ($this instanceof ConnectionProviderInterface)
			if ($this->getConnection() instanceof StringSerializerInterface)
				return $this->getConnection()->quoteStringValue($value);
			else
				throw new \RuntimeException(
					\substr(__METHOD__, \strpos(__METHOD__, '::') + 2) .
					'() is not implemented by ' .
					TypeDescription::getLocalName($this) . ' nor ' .
					TypeDescription::getLocalName(
						$this->getConnection()));
		throw new \RuntimeException(
			\substr(__METHOD__, \strpos(__METHOD__, '::') + 2) .
			'() is not implemented by ' .
			TypeDescription::getLocalName($this));
	}

	public function quoteBinaryData($value)
	{
		if ($this instanceof ConnectionProviderInterface)
			if ($this->getConnection() instanceof BinaryDataSerializerInterface)
				return $this->getConnection()->quoteBinaryData($value);
		throw new \RuntimeException(
			\substr(__METHOD__, \strpos(__METHOD__, '::') + 2) .
			'() is not implemented by ' .
			TypeDescription::getLocalName($this) . ' nor ' .
			TypeDescription::getLocalName($this->getConnection()));
		throw new \RuntimeException(
			\substr(__METHOD__, \strpos(__METHOD__, '::') + 2) .
			'() is not implemented by ' .
			TypeDescription::getLocalName($this));
	}

	public function quoteIdentifier($identifier)
	{
		if ($this instanceof ConnectionProviderInterface)
			if ($this->getConnection() instanceof IdentifierSerializerInterface)
				return $this->getConnection()->quoteIdentifier(
					$identifier);
			else
				throw new \RuntimeException(
					\substr(__METHOD__, \strpos(__METHOD__, '::') + 2) .
					'() is not implemented by ' .
					TypeDescription::getLocalName($this) . ' nor ' .
					TypeDescription::getLocalName(
						$this->getConnection()));
		throw new \RuntimeException(
			\substr(__METHOD__, \strpos(__METHOD__, '::') + 2) .
			'() is not implemented by ' .
			TypeDescription::getLocalName($this));
	}

	public function quoteIdentifierPath($path)
	{
		if ($path instanceof StructureElementInterface)
		{
			$identifier = $this->quoteIdentifier($path->getName());
			while (($path = $path->getParentElement()))
			{
				if (empty($path->getName()) ||
					$path instanceof DatasourceStructure)
					break;

				$identifier = $this->quoteIdentifier($path->getName()) .
					'.' . $identifier;
			}

			return $identifier;
		}

		if (\is_string($path))
			$path = \explode('.', $path);
		elseif ($path instanceof StructureElementIdentifier)
			$path = $path->getPathParts();

		if (!Container::isTraversable($path))
			throw new \InvalidArgumentException(
				StructureElementInterface::class . ', ' .
				StructureElementIdentifier::class .
				', array or string expected. Got ' .
				TypeDescription::getName($path));

		return Container::implodeValues($path, '.',
			function ($name) {
				return $this->quoteIdentifier($name);
			});
	}

	public function serializeTimestamp($value, $dataType)
	{
		if (\is_int($value) || \is_float($value) || \is_string($value))
			$value = new DateTime($value);
		elseif (DateTime::isDateTimeStateArray($value))
			$value = DateTime::createFromArray($value);

		if ($value instanceof \DateTimeInterface)
			$value = $value->format(
				$this->getTimestampTypeStringFormat($dataType));
		else
			$value = TypeConversion::toString($value);

		return $this->quoteStringValue($value);
	}

	public function getPlatformVersion($kind = self::VERSION_CURRENT)
	{
		return Container::keyValue($this->versions, $kind, false);
	}

	public function getStructureFilenameFactory()
	{
		return $this->structureFilenameFactory;
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
			case K::KEYWORD_TEMPORARY:
				return 'TEMPORARY';
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

	public function getTimestampTypeStringFormat($dataType = 0)
	{
		switch ($dataType)
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

		return \DateTime::ISO8601;
	}

	public function getTimestampFormatTokenTranslation($formatToken)
	{
		return false;
	}

	public function newExpression($baseClassname, ...$arguments)
	{
		$reflection = new \ReflectionClass($baseClassname);
		if (!$reflection->implementsInterface(
			ExpressionInterface::class))
			throw new \LogicException(
				$baseClassname . ' is not a ' .
				ExpressionInterface::class);

		return $reflection->newInstanceArgs($arguments);
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

	protected function __construct($parameters)
	{
		if (!Container::isArray($parameters))
			$parameters = [
				self::VERSION_CURRENT => $parameters
			];

		$this->structureFilenameFactory = Container::keyValue(
			$parameters, self::STRUCTURE_FILENAME_FACTORY);

		$version = new SemanticVersion(
			Container::keyValue($parameters, self::VERSION_CURRENT,
				static::DEFAULT_VERSION));
		$this->versions = [
			self::VERSION_CURRENT => $version,
			self::VERSION_COMPATIBILITY => new SemanticVersion(
				$version->slice(SemanticVersion::MAJOR,
					SemanticVersion::MAJOR))
		];

		$this->features = new CascadedValueTree();

		$this->features[K::FEATURE_JOINS] = 0xFFFF;
		$this->features[K::FEATURE_EVENT_ACTIONS] = 0xFFFF;
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
	 * @var callable
	 */
	private $structureFilenameFactory;

	/**
	 *
	 * @var CascadedValueTree
	 */
	private $features;
}
