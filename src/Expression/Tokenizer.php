<?php
namespace NoreSources\SQL\Expression;

use NoreSources\SingletonTrait;
use NoreSources\TypeDescription;
use NoreSources\Expression\BinaryOperation;
use NoreSources\Expression\ExpressionInterface;
use NoreSources\Expression\Identifier;
use NoreSources\Expression\ProcedureInvocation;
use NoreSources\Expression\Set;
use NoreSources\Expression\UnaryOperation;
use NoreSources\Expression\Value;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DataTypeProviderInterface;
use NoreSources\SQL\Structure\ArrayColumnDescription;

class Tokenizer
{

	use SingletonTrait;

	public function tokenizeExpression(ExpressionInterface $expression,
		TokenStream $stream, TokenStreamContextInterface $context)
	{
		if ($expression instanceof TokenizableExpressionInterface)
			return $expression->tokenize($stream, $context);
		elseif ($expression instanceof UnaryOperation)
			return $this->tokenizeUnaryOperation($stream, $context);
		elseif ($expression instanceof ProcedureInvocation)
			return $this->tokenizeProcedureInvocation($expression,
				$stream, $context);
		elseif ($expression instanceof Set)
			return $this->tokenizeSet($expression, $stream, $context);
		elseif ($expression instanceof Value)
			return $this->tokenizeValue($expression, $stream, $context);
		elseif ($expression instanceof Identifier)
			return $stream->identifier($expression->getIdentifier());

		throw new \InvalidArgumentException(
			'Unsupported expression ' .
			TypeDescription::getName($expression));
	}

	public function tokenizeUnaryOperation(UnaryOperation $expression,
		TokenStream $stream, TokenStreamContextInterface $context)
	{
		$stream->text($expression->getOperator());
		if (\preg_match('/[a-z][0-9]/i', $expression->getOperator(),
			$this))
			$stream->space();

		return $stream->expression($expression->getOperand(), $context);
	}

	public function tokenizeBinaryOperation(BinaryOperation $expression,
		TokenStream $stream, TokenStreamContextInterface $context)
	{
		return $stream->expression($expression->getLeftOperand(),
			$context, $this)
			->space()
			->text($expression->getOperator())
			->space()
			->expression($expression->getRightOperand(), $context, $this);
	}

	public function tokenizeProcedureInvocation(
		ProcedureInvocation $expression, TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$stream->keyword($expression->getFunctionName())
			->text('(');
		$index = 0;
		foreach ($expression as $a)
		{
			if ($index++ > 0)
				$stream->text(', ');

			$stream->expression($a, $context, $this);
		}
		return $stream->text(')');
	}

	public function tokenizeValue(Value $expression, TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$type = K::DATATYPE_UNDEFINED;
		if ($expression instanceof DataTypeProviderInterface)
			$type = $expression->getDataType();
		else
			$type = Evaluator::getInstance()->getDataType(
				$expression->getValue());
		return $stream->literal(
			$context->getPlatform()
				->serializeColumnData(
				new ArrayColumnDescription(
					[
						K::COLUMN_DATA_TYPE => K::DATATYPE_NULL | $type
					]), $expression->getValue()));
	}

	public function tokenizeSet(Set $expression, TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$i = 0;
		foreach ($expression as $value)
		{
			if ($i++ > 0)
				$stream->text(',')->space();
			$stream->expression($value, $context, $this);
		}

		return $stream;
	}
}
