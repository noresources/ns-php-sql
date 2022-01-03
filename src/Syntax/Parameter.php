<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax;

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DataTypeProviderInterface;

/**
 * Statement named parameter
 */
class Parameter implements TokenizableExpressionInterface,
	DataTypeProviderInterface
{

	/**
	 *
	 * @var string Parameter name
	 */
	public $name;

	/**
	 *
	 * @param string $name
	 *        	Parameter name
	 */
	public function __construct($name,
		$valueDataType = K::DATATYPE_UNDEFINED)
	{
		$this->name = $name;
		$this->valueDataType = $valueDataType;
	}

	public function getDataType()
	{
		return $this->valueDataType;
	}

	public function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		return $stream->parameter($this->name, $this->getDataType());
	}

	/**
	 * Parameter value preferred data type
	 *
	 * @var integer
	 */
	private $valueDataType;
}