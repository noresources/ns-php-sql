<?php
namespace NoreSources\SQL\Expression;

use NoreSources\Container;
use NoreSources\Expression\Set;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DataTypeProviderInterface;

/**
 * Transitional expression list
 */
class ExpressionList extends Set implements
	TokenizableExpressionInterface, DataTypeProviderInterface
{

	/**
	 * FTransform expression and expression list to an array of expressions
	 *
	 * @param TokenizableExpressionInterface[] ...$expressions
	 * @return array
	 */
	public static function flatten(...$expressions)
	{
		$list = [];
		foreach ($expressions as $x)
		{
			if ($x instanceof ExpressionList)
			{
				$c = -1;
				$x = $x->getArrayCopy();
				while (Container::count($x) != $c)
				{
					$x = self::flatten(...$x);
					$c = Container::count($x);
				}

				\array_push($list, ...$x);
			}
			else
				\array_push($list, $x);
		}

		return $list;
	}

	/**
	 *
	 * @param TokenizableExpressionInterface[] $expressionList
	 * @param string $separator
	 */
	public function __construct($expressionList, $separator = ',')
	{
		parent::__construct($expressionList);
		$this->separator = $separator;
	}

	public function getDataType()
	{
		$type = K::DATATYPE_UNDEFINED;
		foreach ($this as $e)
		{
			if ($x instanceof DataTypeProviderInterface)
			{
				$t = $x->getDataType();
				if ($type == K::DATATYPE_UNDEFINED)
				{
					$type = $t;
					continue;
				}

				if ($t != $type)
					return K::DATATYPE_UNDEFINED;
			}
		}

		return $type;
	}

	/**
	 * Sould not be used
	 *
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\Expression\TokenizableExpressionInterface::tokenize()
	 */
	public function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$i = 0;
		foreach ($this as $value)
		{
			if ($i++ > 0)
				$stream->text($this->separator)->space();
			$stream->expression($value, $context);
		}
		return $stream;
	}

	private $separator;
}
