<?php
/**
 * Copyright © 2012 - 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax;

use NoreSources\ComparableInterface;
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
	 * Keyword constant.
	 * One of Constants\KEYWORD_*.
	 *
	 * @var integer
	 */
	public $keyword;

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

		return K::NOT_COMPARABLE;
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
}