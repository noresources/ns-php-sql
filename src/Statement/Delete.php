<?php

// Namespace
namespace NoreSources\SQL;

// Aliases
use NoreSources as ns;
use NoreSources\SQL\Constants as K;

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
		{
			$x = func_get_arg($i);
			if (!($x instanceof Expression))
				$x = ExpressionEvaluator::evaluate($x);

			$this->whereConstraints->append($x);
		}
	}

	public function tokenize(TokenStream &$stream, StatementContext $context)
	{
		$tableStructure = $context->findTable($this->table->path);

		$context->pushResolverContext($tableStructure);
		$context->setStatementType(K::QUERY_DELETE);

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

		$context->popResolverContext();
		return $stream;
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
