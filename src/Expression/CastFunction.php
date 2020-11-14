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

use NoreSources\Expression\ExpressionInterface;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Structure\ArrayColumnDescription;

/**
 * CAST (expression AS type) shorthand
 */
class CastFunction extends FunctionCall
{

	/**
	 *
	 * @param ExpressionInterface $expression
	 * @param \NoreSources\SQL\Structure\ArrayColumnDescription|\NoreSources\SQL\DBMS\TypeInterface|TypeName|integer $type
	 *        	Type. description
	 */
	public function __construct(ExpressionInterface $expression, $type)
	{
		parent::__construct('cast');

		if (\is_integer($type))
			$type = new ArrayColumnDescription(
				[
					K::COLUMN_DATA_TYPE => $type
				]);

		if (!($type instanceof TypeName))
			$type = new TypeName($type);

		$asOperator = new BinaryOperation('as', $expression, $type);
		$this->appendArgument($asOperator);
	}
}
