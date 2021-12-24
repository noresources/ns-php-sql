<?php
namespace NoreSources\SQL\Syntax;

use NoreSources\Container\Container;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\TypeInterface;
use NoreSources\SQL\Structure\ColumnStructure;
use NoreSources\SQL\Structure\Inspector\StructureInspector;
use NoreSources\SQL\Syntax\Statement\StatementException;
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
	 * @return \NoreSources\SQL\Syntax\ColumnDeclaration
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
	 * @return \NoreSources\SQL\Syntax\ColumnDeclaration
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
		if (!isset($this->columnStructure))
			throw new \RuntimeException(
				ColumnStructure::class . ' not set');
		if (!isset($this->dbmsType))
			throw \RuntimeException(TypeInterface::class . ' not set');

		$columnDeclaration = $platform->queryFeature(
			[
				K::FEATURE_CREATE,
				K::FEATURE_ELEMENT_TABLE,
				K::FEATURE_COLUMN_DECLARATION_FLAGS
			], 0);

		$columnFlags = $this->getColumn()->get(K::COLUMN_FLAGS);

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
		$platform = $context->getPlatform();
		$typeName = $this->dbmsType->getTypeName();
		$typeFlags = Container::keyValue($this->dbmsType, K::TYPE_FLAGS,
			0);
		$columnFlags = $this->columnStructure->get(K::COLUMN_FLAGS);
		$columnDeclaration = $platform->queryFeature(
			[
				K::FEATURE_CREATE,
				K::FEATURE_ELEMENT_TABLE,
				K::FEATURE_COLUMN_DECLARATION_FLAGS
			], 0);

		if (($typeFlags & K::TYPE_FLAG_SIGNNESS) &&
			($columnFlags & K::COLUMN_FLAG_UNSIGNED) &&
			($columnDeclaration & K::FEATURE_COLUMN_SIGNNESS_TYPE_PREFIX))
		{
			$stream->keyword('unsigned')->space();
		}

		$stream->identifier($typeName);

		$this->tokenizeTypeConstraints($stream, $context);
	}

	public function tokenizeTypeConstraints(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$platform = $context->getPlatform();
		$inspector = StructureInspector::getInstance();
		$columnFlags = $this->columnStructure->get(K::COLUMN_FLAGS);
		$columnDeclaration = $platform->queryFeature(
			[
				K::FEATURE_CREATE,
				K::FEATURE_ELEMENT_TABLE,
				K::FEATURE_COLUMN_DECLARATION_FLAGS
			], 0);

		$keyConstraintFlags = K::CONSTRAINT_COLUMN_KEY |
			K::CONSTRAINT_COLUMN_FOREIGN_KEY;
		$isKey = (($inspector->getTableColumnConstraintFlags(
			$this->columnStructure) & $keyConstraintFlags) != 0);
		$typeFlags = Container::keyValue($this->dbmsType, K::TYPE_FLAGS,
			0);

		$lengthSupport = (($typeFlags & K::TYPE_FLAG_LENGTH) ==
			K::TYPE_FLAG_LENGTH);

		$fractionScaleSupport = (($typeFlags &
			K::TYPE_FLAG_FRACTION_SCALE) == K::TYPE_FLAG_FRACTION_SCALE);

		$hasLength = $this->columnStructure->has(K::COLUMN_LENGTH);
		$hasFractionScale = $this->columnStructure->has(
			K::COLUMN_FRACTION_SCALE);

		$hasEnumeration = $this->columnStructure->has(
			K::COLUMN_ENUMERATION);

		$mandatoryLength = (($typeFlags & K::TYPE_FLAG_MANDATORY_LENGTH) ==
			K::TYPE_FLAG_MANDATORY_LENGTH) ||
			($isKey &&
			($columnDeclaration & K::FEATURE_COLUMN_KEY_MANDATORY_LENGTH));

		if ($hasEnumeration &&
			($columnDeclaration & K::FEATURE_COLUMN_ENUM))
		{
			$stream->text('(');
			$values = $this->columnStructure->get(K::COLUMN_ENUMERATION);
			$i = 0;
			foreach ($values as $value)
			{
				if ($i++ > 0)
					$stream->text(',')->space();
				$stream->expression($value, $context);
			}
			$stream->text(')');
		}
		elseif ($lengthSupport)
		{
			$scale = null;
			$length = null;

			if ($fractionScaleSupport &&
				$this->columnStructure->has(K::COLUMN_FRACTION_SCALE) &&
				$fractionScaleSupport)
			{
				$scale = $this->columnStructure->get(
					K::COLUMN_FRACTION_SCALE);
			}

			if ($hasLength)
			{
				$length = $this->columnStructure->get(K::COLUMN_LENGTH);
			}
			elseif ($hasFractionScale && $fractionScaleSupport)
			{
				$length = $this->dbmsType->getTypeMaxLength();
				if (\is_infinite($length))
				{
					if ($platform instanceof LoggerInterface)
						$platform->warning(
							'Specifying scale without precision on a type with undefined max length may produce unexpected values');
					$length = $scale * 2;
				}
				else
					$length -= $scale;
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
		$inspector = StructureInspector::getInstance();
		$columnDeclaration = $platform->queryFeature(
			[
				K::FEATURE_CREATE,
				K::FEATURE_ELEMENT_TABLE,
				K::FEATURE_COLUMN_DECLARATION_FLAGS
			], 0);

		$typeFlags = Container::keyValue($this->dbmsType, K::TYPE_FLAGS,
			0);
		$columnFlags = $this->columnStructure->get(K::COLUMN_FLAGS);
		$dataType = $this->columnStructure->get(K::COLUMN_DATA_TYPE);
		$nullable = ($dataType & K::DATATYPE_NULL);
		$dataType &= ~K::DATATYPE_NULL;
		$constraintFlags = $inspector->getTableColumnConstraintFlags(
			$this->columnStructure);
		$nullSpecification = ($nullable == false ||
			(($constraintFlags & K::CONSTRAINT_COLUMN_KEY) == 0));

		if (($typeFlags & K::TYPE_FLAG_SIGNNESS) &&
			($columnFlags & K::COLUMN_FLAG_UNSIGNED) &&
			(($columnDeclaration & K::FEATURE_COLUMN_SIGNNESS_TYPE_PREFIX) ==
			0))
		{
			if ($stream->count())
				$stream->space();
			$stream->keyword('unsigned');
		}

		if ($nullSpecification)
		{
			if ($stream->count())
				$stream->space();
			if (!$nullable)
				$stream->keyword('not')->space();
			$stream->keyword('null');
		}

		if ($this->columnStructure->has(K::COLUMN_DEFAULT_VALUE))
		{
			if ($stream->count())
				$stream->space();
			$this->tokenizeColumnDefaultValue($stream, $context);
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
		$stream->keyword('DEFAULT')
			->space()
			->expression(
			$this->columnStructure->get(K::COLUMN_DEFAULT_VALUE),
			$context);
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
