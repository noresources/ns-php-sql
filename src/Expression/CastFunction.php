<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\Expression;

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Structure\ArrayColumnPropertyMap;

/**
 * CAST (expression AS type) shorthand
 */
class CastFunction extends FunctionCall
{

	/**
	 *
	 * @param TokenizableExpression $expression
	 * @param \NoreSources\SQL\Structure\ColumnPropertyMap|\NoreSources\SQL\DBMS\TypeInterface|TypeName|integer $type
	 *        	Type. description
	 */
	public function __construct(TokenizableExpression $expression, $type)
	{
		parent::__construct('cast');

		if (\is_integer($type))
			$type = new ArrayColumnPropertyMap([
				K::COLUMN_DATA_TYPE => $type
			]);

		if (!($type instanceof TypeName))
			$type = new TypeName($type);

		$asOperator = new BinaryOperation('as', $expression, $type);
		$this->appendArgument($asOperator);
	}
}










