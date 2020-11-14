<?php
namespace NoreSources\SQL\Expression;

use NoreSources\Container;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\TypeInterface;
use NoreSources\SQL\Statement\StatementException;
use NoreSources\SQL\Structure\ColumnStructure;
use Psr\Log\LoggerInterface;

class ColumnDeclaration implements TokenizableExpressionInterface
{

	public function __construct(ColumnStructure $column = null,
		TypeInterface $type = null)
	{
		if (isset($column))
			$this->columnStructure = $column;
		if (isset($type))
			$this->dbmsType = $type;
	}

	/**
	 *
	 * @param ColumnStructure $column
	 * @return \NoreSources\SQL\Expression\ColumnDeclaration
	 */
	public function column(ColumnStructure $column)
	{
		$this->columnStructure = $column;
		return $this;
	}

	/**
	 *
	 * @return \NoreSources\SQL\Structure\ColumnStructure
	 */
	public function getColumn()
	{
		return $this->columnStructure;
	}

	/**
	 *
	 * @return \NoreSources\SQL\DBMS\TypeInterface
	 */
	public function getType()
	{
		return $this->dbmsType;
	}

	/**
	 *
	 * @param TypeInterface $type
	 * @return \NoreSources\SQL\Expression\ColumnDeclaration
	 */
	public function type(TypeInterface $type)
	{
		$this->dbmsType = $type;
		return $this;
	}

	public function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$platform = $context->getPlatform();
		$logger = null;
		if ($platform instanceof LoggerInterface)
			$logger = $platform;
		if (!isset($this->columnStructure))
			throw new \RuntimeException(
				ColumnStructure::class . ' not set');
		if (!isset($this->dbmsType))
			throw \RuntimeException(TypeInterface::class . ' not set');

		$columnDeclaration = $platform->queryFeature(
			[
				K::PLATFORM_FEATURE_CREATE,
				K::PLATFORM_FEATURE_TABLE,
				K::PLATFORM_FEATURE_COLUMN_DECLARATION_FLAGS
			], 0);

		$columnFlags = $this->getColumn()->getColumnProperty(
			K::COLUMN_FLAGS);

		$stream->identifier(
			$context->getPlatform()
				->quoteIdentifier($this->columnStructure->getName()))
			->space();

		$this->tokenizeType($stream, $context);

		$this->tokenizeColumnConstraints($stream, $context);

		return $stream;
	}

	public function tokenizeType(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$typeName = $this->dbmsType->getTypeName();

		$stream->identifier($typeName);

		$this->tokenizeTypeConstraints($stream, $context);
	}

	public function tokenizeTypeConstraints(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$platform = $context->getPlatform();
		$columnFlags = $this->columnStructure->getColumnProperty(
			K::COLUMN_FLAGS);
		$columnDeclaration = $platform->queryFeature(
			[
				K::PLATFORM_FEATURE_CREATE,
				K::PLATFORM_FEATURE_TABLE,
				K::PLATFORM_FEATURE_COLUMN_DECLARATION_FLAGS
			], 0);

		$isPrimary = (($this->columnStructure->getConstraintFlags() &
			K::COLUMN_CONSTRAINT_PRIMARY_KEY) ==
			K::COLUMN_CONSTRAINT_PRIMARY_KEY);
		$typeFlags = Container::keyValue($this->dbmsType, K::TYPE_FLAGS,
			0);

		$lengthSupport = (($typeFlags & K::TYPE_FLAG_LENGTH) ==
			K::TYPE_FLAG_LENGTH);

		$fractionScaleSupport = (($typeFlags &
			K::TYPE_FLAG_FRACTION_SCALE) == K::TYPE_FLAG_FRACTION_SCALE);

		$hasLength = $this->columnStructure->hasColumnProperty(
			K::COLUMN_LENGTH);
		$hasFractionScale = $this->columnStructure->hasColumnProperty(
			K::COLUMN_FRACTION_SCALE);

		$hasEnumeration = $this->columnStructure->hasColumnProperty(
			K::COLUMN_ENUMERATION);

		$mandatoryLength = (($typeFlags & K::TYPE_FLAG_MANDATORY_LENGTH) ==
			K::TYPE_FLAG_MANDATORY_LENGTH) ||
			($isPrimary &&
			($columnDeclaration &
			K::PLATFORM_FEATURE_COLUMN_KEY_MANDATORY_LENGTH));

		if ($hasEnumeration &&
			($columnDeclaration & K::PLATFORM_FEATURE_COLUMN_ENUM))
		{
			$stream->text('(');
			$values = $this->columnStructure->getColumnProperty(
				K::COLUMN_ENUMERATION);
			$i = 0;
			foreach ($values as $value)
			{
				if ($i++ > 0)
					$stream->text(', ');
				$stream->expression($value, $context);
			}
			$stream->text(')');
		}
		elseif ($lengthSupport)
		{
			$scale = null;
			$length = null;

			if ($fractionScaleSupport &&
				$this->columnStructure->hasColumnProperty(
					K::COLUMN_FRACTION_SCALE) && $fractionScaleSupport)
			{
				$scale = $this->columnStructure->getColumnProperty(
					K::COLUMN_FRACTION_SCALE);
			}

			if ($hasLength)
			{
				$length = $this->columnStructure->getColumnProperty(
					K::COLUMN_LENGTH);
			}
			elseif ($hasFractionScale && $fractionScaleSupport)
			{
				$length = $this->dbmsType->getTypeMaxLength();
				if (\is_infinite($length))
				{
					if ($logger instanceof LoggerInterface)
						$logger->warning(
							'Specifying scale without precision on a type with undefined max length may produce unexpected values');
					$length = $scale * 2;
				}
			}
			elseif ($mandatoryLength)
			{
				$length = $this->dbmsType->getTypeMaxLength();
				if (\is_infinite($length))
					throw new StatementException($this,
						$this->columnStructure->getName() .
						' column require length specification but type ' .
						$this->dbmsType->getTypeName() .
						' max length is unspecified');
			}
			elseif ($this->dbmsType->has(K::TYPE_DEFAULT_LENGTH) &&
				($this->dbmsType->get(K::TYPE_DEFAULT_LENGTH) <
				($typeMaxLength = $this->dbmsType->getTypeMaxLength())) &&
				!\is_infinite($typeMaxLength))
			{
				$length = $typeMaxLength;
			}

			if ($length != null)
				$this->tokenizeTypeLengthConstraint($stream, $context,
					$length, $scale);
		}
		elseif ($mandatoryLength)
		{
			throw new StatementException($this,
				$this->columnStructure->getName() .
				' column require length specification but type ' .
				$this->dbmsType->getTypeName() . ' does not support it');
		}
	}

	public function tokenizeTypeLengthConstraint(TokenStream $stream,
		TokenStreamContextInterface $context, $length, $scale = null)
	{
		$stream->text('(')->literal($length);
		if ($scale !== null)
		{
			$stream->text(',')
				->space()
				->literal($scale);
		}
		$stream->text(')');

		return $stream;
	}

	public function tokenizeColumnConstraints(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$platform = $context->getPlatform();
		$typeFlags = Container::keyValue($this->dbmsType, K::TYPE_FLAGS,
			0);
		$columnFlags = $this->columnStructure->getColumnProperty(
			K::COLUMN_FLAGS);
		$dataType = $this->columnStructure->getColumnProperty(
			K::COLUMN_DATA_TYPE);
		$nullable = ($dataType & K::DATATYPE_NULL);
		$dataType &= ~K::DATATYPE_NULL;

		if ($this->columnStructure->hasColumnProperty(
			K::COLUMN_DEFAULT_VALUE))
		{
			if ($stream->count())
				$stream->space();
			$this->tokenizeColumnDefaultValue($stream, $context);
		}

		if (($typeFlags & K::TYPE_FLAG_SIGNNESS) &&
			($columnFlags & K::COLUMN_FLAG_UNSIGNED))
		{
			if ($stream->count())
				$stream->space();
			$stream->keyword('unsigned');
		}

		if (!$nullable)
		{
			if ($stream->count())
				$stream->space();
			$stream->keyword('not')
				->space()
				->keyword('null');
		}

		if ($columnFlags & K::COLUMN_FLAG_AUTO_INCREMENT)
		{

			$ai = $platform->getKeyword(K::KEYWORD_AUTOINCREMENT);
			if (\strlen($ai))
			{
				if ($stream->count())
					$stream->space();

				$stream->keyword($ai);
			}
		}

		return $stream;
	}

	public function tokenizeColumnDefaultValue(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$v = Evaluator::evaluate(
			$this->columnStructure->getColumnProperty(
				K::COLUMN_DEFAULT_VALUE));
		$stream->keyword('DEFAULT')
			->space()
			->expression($v, $context);
	}

	/**
	 *
	 * @var ColumnStructure
	 */
	private $columnStructure;

	/**
	 *
	 * @var TypeInterface
	 */
	private $dbmsType;
}
