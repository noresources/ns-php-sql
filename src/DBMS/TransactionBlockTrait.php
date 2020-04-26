<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS;

/**
 * Reference implementation of TransactionBlockInterface
 */
trait TransactionBlockTrait
{

	/**
	 * Default behavior of TransactionBlockInterface
	 */
	public function __destruct()
	{
		$this->endTransactionBlock(false);
	}

	public function begin()
	{
		if ($this->getBlockState() != TransactionBlockInterface::STATE_UNDEFINED)
			throw new \LogicException(__METHOD__ . ' called probably twice');

		$this->beginTask();
		$this->blockState = TransactionBlockInterface::STATE_PENDING;
	}

	public function commit()
	{
		if ($this->getBlockState() == TransactionBlockInterface::STATE_ROLLED_BACK)
			throw new TransactionBlockException(
				'Attempting to commit a transaction block that was rolled back by a outer block.',
				TransactionBlockException::INVALID_STATE);

		if ($this->getBlockState() != TransactionBlockInterface::STATE_PENDING)
			return; // Already committed by outer block

		$this->commitTask();
		$this->propagateBlockState(TransactionBlockInterface::STATE_COMMITTED);
	}

	public function rollback()
	{
		if ($this->getBlockState() == TransactionBlockInterface::STATE_COMMITTED)
			throw new TransactionBlockException(
				'Attempting to rollback a transaction block that was committed by a outer block.',
				TransactionBlockException::INVALID_STATE);

		if ($this->getBlockState() != TransactionBlockInterface::STATE_PENDING)
			return; // already rolled back by outer block

		$this->rollbackTask();
		$this->propagateBlockState(TransactionBlockInterface::STATE_ROLLED_BACK);
	}

	public function getBlockState()
	{
		return $this->blockState;
	}

	public function getBlockName()
	{
		return $this->blockName;
	}

	public function endTransactionBlock($commit = false)
	{
		if ($this->getBlockState() == TransactionBlockInterface::STATE_PENDING)
		{
			if ($commit)
				$this->commit();
			else
				$this->rollback();
		}
	}

	/**
	 * Initialize the trait private member
	 *
	 * @param string $name
	 *        	Use defined, DBMS-independant transaction/save point name.
	 */
	protected function initializeTransactionBlock($name)
	{
		$this->blockState = TransactionBlockInterface::STATE_UNDEFINED;
		$this->blockName = $name;
	}

	/**
	 * DBMS-dependant transaction/save point start task.
	 *
	 * This method must be re-implemented in classes which use the TransactionBlockTrait.
	 *
	 * @throws \LogicException
	 */
	protected function beginTask()
	{
		throw new \LogicException(static::class . ' must re-implements ' . __METHOD__);
	}

	/**
	 * DBMS-dependant commit task.
	 *
	 * This method must be re-implemented in classes which use the TransactionBlockTrait.
	 *
	 * @throws \LogicException
	 */
	protected function commitTask()
	{
		throw new \LogicException(static::class . ' must re-implements ' . __METHOD__);
	}

	/**
	 * DBMS dependant rollback task.
	 *
	 * This method must be re-implemented in classes which use the TransactionBlockTrait.
	 *
	 * @throws \LogicException
	 */
	protected function rollbackTask()
	{
		throw new \LogicException(static::class . ' must re-implements ' . __METHOD__);
	}

	public function propagateBlockState($newState)
	{
		$this->blockState = $newState;
		$n = $this->getNextElement();
		if ($n instanceof TransactionBlockInterface &&
			($n->getBlockState() == TransactionBlockInterface::STATE_PENDING))
		{
			$this->getNextElement()->propagateBlockState($newState);
		}
	}

	/**
	 *
	 * @var string
	 */
	private $blockName;

	/**
	 *
	 * @var integer
	 */
	private $blockState;
}