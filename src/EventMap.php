<?php

/**
 * Copyright © 2020 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL;

use NoreSources\Container\Container;
use Psr\Container\ContainerInterface;

class EventMap implements ContainerInterface, \IteratorAggregate
{

	public function __construct()
	{}

	/**
	 *
	 * @param mixed $id
	 * @param mixed $action
	 * @return \NoreSources\SQL\Event
	 */
	public function on($id, $action)
	{
		if (!isset($this->events))
			$this->events = [];
		if ($action === null)
			Container::removeKey($this->events, $id);
		else
			Container::setValue($this->events, $id, $action);
		return $this;
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \Psr\Container\ContainerInterface::get()
	 */
	public function get($id)
	{
		if (!(isset($this->events) &&
			Container::keyExists($this->events, $id)))
			throw new ItemNotFoundException('Event', $id);
		return Container::keyValue($this->events, $id);
	}

	/**
	 *
	 * @param mixed $id
	 * @return boolean
	 */
	public function has($id)
	{
		if (isset($this->events))
			return Container::keyExists($this->events, $id);
		return false;
	}

	public function getIterator()
	{
		if (!isset($this->events))
			return [];
		return new \ArrayIterator($this->events);
	}

	/**
	 *
	 * @var array
	 */
	private $events;
}
