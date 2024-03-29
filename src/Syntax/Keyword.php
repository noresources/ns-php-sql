<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax;

use NoreSources\ComparableInterface;
use NoreSources\NotComparableException;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DataTypeProviderInterface;
use NoreSources\SQL\DBMS\Reference\ReferencePlatform;

/**
 * SQL language keyword which may have different translation in DBMS dialect
 */
class Keyword implements TokenizableExpressionInterface,
	DataTypeProviderInterface, ComparableInterface
{

	/**
	 *
	 * @param integer $keyword
	 */
	public function __construct($keyword)
	{
		$this->keyword = $keyword;
	}

	public function __toString()
	{
		$p = new ReferencePlatform();
		return $p->getKeyword($this->getKeyword());
	}

	/**
	 *
	 * @return number Keyword constant value
	 */
	public function getKeyword()
	{
		return $this->keyword;
	}

	public function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		return $stream->keyword($this->keyword);
	}

	public function compare($b)
	{
		if ($b instanceof Keyword)
			return $this->keyword - $b->getKeyword();

		if ($this->keyword == K::KEYWORD_TRUE && \is_bool($b) && $b)
			return 0;
		if ($this->keyword == K::KEYWORD_FALSE && \is_bool($b) && !$b)
			return 0;
		if ($this->keyword == K::KEYWORD_NULL && \is_null($b))
			return 0;

		throw new NotComparableException($this, $b);
	}

	/**
	 *
	 * @throws \RuntimeException
	 * @return mixed Literal representation of keyword if available.
	 */
	public function getValue()
	{
		switch ($this->keyword)
		{
			case K::KEYWORD_TRUE:
				return true;
			case K::KEYWORD_FALSE:
				return false;
			case K::KEYWORD_NULL:
				return null;
			case K::KEYWORD_CURRENT_TIMESTAMP:
				return new \DateTime('now');
		}

		throw new \RuntimeException('No value conversion for keyword');
	}

	public function getDataType()
	{
		switch ($this->keyword)
		{
			case K::KEYWORD_TRUE:
			case K::KEYWORD_FALSE:
				return K::DATATYPE_BOOLEAN;
			case K::KEYWORD_NULL:
				return K::DATATYPE_NULL;
			case K::KEYWORD_CURRENT_TIMESTAMP:
				return K::DATATYPE_TIMESTAMP;
		}

		return K::DATATYPE_UNDEFINED;
	}

	/**
	 * Keyword constant.
	 * One of Constants\KEYWORD_*.
	 *
	 * @var integer
	 */
	private $keyword;
}