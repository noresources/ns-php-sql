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

/**
 * CAST (expression AS type) shorthand
 */
class CastFunction extends FunctionCall
{

	/**
	 *
	 * @param TokenizableExpression $expression
	 * @param \NoreSources\SQL\Structure\ColumnPropertyMap|\NoreSources\SQL\DBMS\TypeInterface|TypeName $type
	 */
	public function __construct(TokenizableExpression $expression, $type)
	{
		parent::__construct('cast');

		if (!($type instanceof TypeName))
			$type = new TypeName($type);

		$asOperator = new BinaryOperation('as', $expression, $type);
		$this->appendArgument($asOperator);
	}
}










