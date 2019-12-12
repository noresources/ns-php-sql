<?php

// Namespace
namespace NoreSources\SQL\Statement;

// Aliases
use NoreSources\SQL\Constants as K;
use NoreSources\Expression\Expression;
use NoreSources\SQL\TableStructure;
use NoreSources\SQL\TokenStream;
use NoreSources\SQL\Expression\Evaluator;
use NoreSources\SQL\Expression\TableReference;

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
				$x = Evaluator::evaluate($x);

			$this->whereConstraints->append($x);
		}
	}

	public function tokenize(TokenStream &$stream, BuildContext $context)
	{
		$tableStructure = $context->findTable($this->table->path);

		$context->pushResolverContext($tableStructure);
		$context->setStatementType(K::QUERY_DELETE);

		$stream->keyword('delete')
			->space()
			->keyword('from')
			->space()
			->expression($this->table, $context);

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