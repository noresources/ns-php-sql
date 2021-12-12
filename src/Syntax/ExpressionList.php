<?php
/**
 * Copyright © 2020 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax;

use NoreSources\Container\Container;
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
	 * @param ExpressionInterface[] ...$expressions
	 * @return array
	 */
	public static function flatten(...$expressions)
	{
		$list = [];
		foreach ($expressions as $x)
		{
			if ($x instanceof Set)
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
	 * @param ExpressionInterface[] $expressionList
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
	 * @see ExpressionInterface::tokenize()
	 */
	public function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		return Tokenizer::getInstance()->tokenizeSet($this, $stream,
			$context);
	}

	private $separator;
}
