<?php

// Namespace
namespace NoreSources\SQL;

// Aliases
use NoreSources as ns;
use NoreSources\ArrayUtil;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\ExpressionEvaluator as X;

class DeleteQuery extends Statement
{

	/**
	 *
	 * @param TableStructure|string $table
	 */
	public function __construct($table)
	{
		if ($table instanceof TableStructure)
		{
			$table = $table->getPath();
		}

		$this->table = new TableReference($table);

		$this->whereConstraints = new \ArrayObject();
	}

	/**
	 * WHERE constraints
	 *
	 * @param
	 *        	Evaluable ...
	 */
	public function where()
	{
		$c = func_num_args();
		for ($i = 0; $i < $c; $i++)
			$this->whereConstraints->append(func_get_arg($i));
	}

	public function tokenize(TokenStream &$stream, StatementContext $context)
	{
		$tableStructure = $context->findTable($this->table->path);

		$stream->keyword('delete')
			->space()
			->keyword('from')
			->space()
			->identifier($context->getCanonicalName($tableStructure));

		if ($this->whereConstraints->count())
		{
			$stream->space()
				->keyword('where')
				->space()
				->constraints($this->whereConstraints, $context);
		}

		return $stream;
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \NoreSources\SQL\Expression::traverse()
	 */
	public function traverse($callable, StatementContext $context, $flags = 0)
	{
		call_user_func($callable, $this, $context, $flags);
		foreach ($this->whereConstraints as $e)
		{
			if ($e instanceof Expression)
				call_user_func($callable, $e, $context, $flags);
		}
	}

	/**
	 *
	 * @var TableReference
	 */
	private $table;

	/**
	 * WHERE conditions
	 *
	 * @var \ArrayObject
	 */
	private $whereConstraints;
}
