<?php
/**
 * Copyright © 2012 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\Traits;

use NoreSources\SQL\DBMS\TransactionBlockInterface;

/**
 * Reference implementation af a TransactionBlockInterface factory
 */
trait TransactionStackTrait
{

	/**
	 * Default behavior for destructor.
	 * Rollback any pending transaction block.
	 *
	 * If a class using this trait has a user-defined destructor,
	 * the content of this method should be added to it.
	 */
	public function __destruct()
	{
		$this->endTransactions(false);
	}

	/**
	 * ConnectionInterface method implementation.
	 *
	 * @param string $name
	 *        	User defined, DBMS independant transaction/save point name
	 * @throws \RuntimeException
	 * @return TransactionBlockInterface
	 */
	public function newTransactionBlock($name = null)
	{
		$depth = 0;
		$block = $this->transactionStack;

		while ($block instanceof TransactionBlockInterface)
		{
			if ($block->getBlockState() !=
				TransactionBlockInterface::STATE_PENDING)
			{
				$block = $block->getPreviousElement();
				break;
			}

			$depth++;

			if (!($block->getNextElement() instanceof TransactionBlockInterface))
				break;

			$block = $block->getNextElement();
		}

		if (!\is_callable($this->blockFactory))
			throw new \RuntimeException(
				'Transaction block factory was not defined.');

		$newBlock = call_user_func($this->blockFactory, $depth, $name);
		if ($depth == 0)
			$this->transactionStack = $newBlock;
		else
			$newBlock->insertAfter($block);

		$newBlock->begin();
		return $newBlock;
	}

	/**
	 * Define the TransactionBlockInterface factory
	 *
	 * @param callable $callable
	 *        	a function with the given prototype:
	 *        	callable ($depth, $name): TransactionBlockInterface
	 */
	private function setTransactionBlockFactory($callable)
	{
		$this->blockFactory = $callable;
	}

	/**
	 * This method should have to ve called on disconnection or destruction of the
	 * ConnectionInterface.
	 *
	 * @param boolean $commit
	 *        	If TRUE, commit pending transaction block. Otherwise, rollback.
	 */
	private function endTransactions($commit)
	{
		if ($this->transactionStack instanceof TransactionBlockInterface)
		{
			if ($this->transactionStack->getBlockState() ==
				TransactionBlockInterface::STATE_PENDING)
			{
				if ($commit)
					$this->transactionStack->commit();
				else
					$this->transactionStack->rollback();
			}
		}

		$this->transactionStack = null;
	}

	/**
	 *
	 * @var TransactionBlockInterface
	 */
	private $transactionStack;

	/**
	 *
	 * @var callable A TransactionBlock factory with the given prototype.
	 *      function ($depth, $name) : TransactionBlockInterface
	 *
	 */
	private $blockFactory;
}