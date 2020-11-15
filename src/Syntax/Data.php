<?php
namespace NoreSources\SQL\Syntax;

use NoreSources\Expression\Value;
use NoreSources\SQL\DataTypeProviderInterface;

class Data extends Value implements TokenizableExpressionInterface,
	DataTypeProviderInterface
{

	/**
	 *
	 * @param mixedn $value
	 *        	Literal value
	 * @param integer|NULLn $dataType
	 *        	Literal data type. If NULL, the data type is deducted from $value
	 */
	public function __construct($value, $dataType = null)
	{
		$this->setValue($value);
		$this->setDataType($dataType);
	}

	/**
	 *
	 * @param DataTypeProviderInterface|integer|NULL $dataType
	 *        	Data type. If NULL, data type is deducted from value
	 */
	public function setDataType($dataType)
	{
		if ($dataType instanceof DataTypeProviderInterface)
			$dataType = $dataType->getDataType();
		if ($dataType === null)
			$dataType = Evaluator::getInstance()->getDataType(
				$this->getValue());
		$this->dataType = $dataType;
	}

	public function getDataType()
	{
		return $this->dataType;
	}

	public function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		return $stream->literal(
			$context->getPlatform()
				->serializeData($this->getValue(), $this->dataType));
	}

	/**
	 *
	 * @var integer Data type
	 */
	private $dataType;
}
