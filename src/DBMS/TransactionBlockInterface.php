<?php
/**
 * Copyright © 2012 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS;

use NoreSources\Container\ChainElementInterface;
use NoreSources\SQL\Constants as K;

/**
 * Transaction block.
 *
 * A TransactionBlockInterface may represents either a transaction block or a transaction save point
 * (nested transaction block)..
 */
interface TransactionBlockInterface extends ChainElementInterface
{

	const STATE_UNDEFINED = K::TRANSACTION_STATE_UNDEFINED;

	/**
	 * Transaction block state.
	 *
	 * Transaction block pperation are not yet committed nor rolled back.
	 *
	 * @var integer
	 * @used-by getBlockState()
	 */
	const STATE_PENDING = K::TRANSACTION_STATE_PENDING;

	/**
	 * Transaction block state.
	 *
	 * Transaction block was rolled back.
	 *
	 * @var integer
	 * @used-by getBlockState()
	 */
	const STATE_ROLLED_BACK = K::TRANSACTION_STATE_ROLLED_BACK;

	/**
	 * Transaction block state.
	 *
	 * Transaction block was committed.
	 *
	 * @var integer
	 * @used-by getBlockState()
	 */
	const STATE_COMMITTED = K::TRANSACTION_STATE_COMMITTED;

	/**
	 * Initiate transaction block.
	 *
	 * After this call, the block state must be STATE_PENDING.
	 */
	function begin();

	/**
	 * Commit transaction block.
	 *
	 * After this call, the state of the instance and all inner blocks must be STATE_COMMITTED
	 */
	function commit();

	/**
	 * Rollback transaction block.
	 *
	 * After this call, the state of the instance and all inner blocks must be STATE_ROLLED_BACK
	 */
	function rollback();

	/**
	 *
	 * @return string User-defined, DBMS independant transaction/save point name
	 */
	function getBlockName();

	/**
	 *
	 * @return integer
	 */
	function getBlockState();

	/**
	 * FOR INTERNAL USE ONLY.
	 * Users should not call this method manually.
	 *
	 * Set transaction block state and propagate it to all inner blocks.
	 *
	 * @param integer $newState
	 */
	function propagateBlockState($newState);
}