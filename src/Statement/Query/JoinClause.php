<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */

//
namespace NoreSources\SQL\Statement\Query;

use NoreSources\SQL\Expression\DataRowContainerReference;
use NoreSources\SQL\Expression\TokenStream;
use NoreSources\SQL\Expression\TokenStreamContextInterface;
use NoreSources\SQL\Expression\TokenizableExpressionInterface;
use NoreSources\SQL\Statement\Traits\ConstraintExpressionListTrait;

/**
 * SELECT query JOIN clause
 */
class JoinClause implements TokenizableExpressionInterface
{
	use ConstraintExpressionListTrait;

	/**
	 *
	 * @var integer
	 */
	public $operator;

	/**
	 * Table or subquery
	 *
	 * @var DataRowContainerReference
	 */
	public $subject;

	public function __construct($operator,
		DataRowContainerReference $subject /*, on ...*/)
	{
		$this->operator = $operator;
		$this->subject = $subject;
		$this->constraints = null;

		$args = func_get_args();
		array_shift($args);
		array_shift($args);

		call_user_func_array([
			$this,
			'on'
		], $args);
	}

	public function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$stream->keyword(
			$context->getPlatform()
				->getJoinOperator($this->operator));

		$stream->space()->expression($this->subject, $context);

		if ($this->constraints->count())
		{
			$stream->space()
				->keyword('on')
				->space()
				->constraints($this->constraints, $context);
		}

		return $stream;
	}

	public function getExpressionDataType()
	{
		return K::DATATYPE_UNDEFINED;
	}

	/**
	 *
	 * @param
	 *        	... List of constraint expressions
	 * @return \NoreSources\SQL\Statement\JoinClause
	 */
	public function on()
	{
		if (!($this->constraints instanceof \ArrayObject))
			$this->constraints = new \ArrayObject();
		return $this->addConstraints($this->constraints, func_get_args());
	}

	private $constraints;
}
