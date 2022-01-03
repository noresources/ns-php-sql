<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS;

use NoreSources\SingletonTrait;
use NoreSources\SQL\DBMS\Traits\DefaultDataUnserializerTrait;

/**
 * Reference implementation of DataUnserializerInterface
 */
class DefaultDataUnserializer implements DataUnserializerInterface
{
	use DefaultDataUnserializerTrait;

	use SingletonTrait;
}