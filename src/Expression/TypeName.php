<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\Expression;

use NoreSources\TypeDescription;
use NoreSources\SQL\DBMS\TypeInterface;
use NoreSources\SQL\Structure\ColumnDescriptionInterface;
use NoreSources\SQL\Structure\ColumnStructure;

/**
 * Type name identifier
 */
class TypeName implements TokenizableExpressionInterface
{

	/**
	 *
	 * @param \NoreSources\SQL\Structure\ColumnDescriptionInterface|\NoreSources\SQL\DBMS\TypeInterface $type
	 */
	public function __construct($type)
	{
		$this->setType($type);
	}

	/**
	 *
	 * @return \NoreSources\SQL\Structure\ColumnDescriptionInterface|\NoreSources\SQL\DBMS\TypeInterface
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 *
	 * @param \NoreSources\SQL\Structure\ColumnDescriptionInterface|\NoreSources\SQL\DBMS\TypeInterface $type
	 */
	public function setType($type)
	{
		if (!($type instanceof TypeInterface || $type instanceof ColumnDescriptionInterface))
		{
			throw new \InvalidArgumentException(
				TypeInterface::class . ' or ' . ColumnDescriptionInterface::class . ' expected');
			$this->type = $type;
		}

		$this->type = $type;
	}

	public function tokenize(TokenStream $stream, TokenStreamContextInterface $context)
	{
		$type = $this->type;
		if ($type instanceof ColumnDescriptionInterface)
		{
			if (!($type instanceof ColumnStructure))
			{
				if ($type instanceof ColumnDescriptionInterface)
				{
					$s = new ColumnStructure('runtime_type', null);
					foreach ($type->getColumnProperties() as $key => $value)
						$s->setColumnProperty($key, $value);
					$type = $s;
				}
			}

			$type = $context->getStatementBuilder()->getColumnType($type);
		}

		if (!($type instanceof TypeInterface))
			throw new \RuntimeException(
				'Unable to get ' . TypeInterface::class . ' from ' .
				TypeDescription::getName($this->type));

		/**
		 *
		 * @var TypeInterface $type
		 */

		return $stream->identifier($type->getTypeName());
	}

	/**
	 *
	 * @var \NoreSources\SQL\Structure\ColumnDescriptionInterface|\NoreSources\SQL\DBMS\TypeInterface
	 */
	private $type;
}