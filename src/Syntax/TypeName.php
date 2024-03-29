<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax;

use NoreSources\SQL\DBMS\TypeInterface;
use NoreSources\SQL\Structure\ColumnDescriptionInterface;
use NoreSources\SQL\Structure\ColumnStructure;
use NoreSources\SQL\Structure\Inspector\StructureInspector;
use NoreSources\Type\TypeDescription;

/**
 * Type name identifier
 */
class TypeName implements TokenizableExpressionInterface
{

	/**
	 *
	 * @param ColumnDescriptionInterface|TypeInterface $type
	 */
	public function __construct($type)
	{
		$this->setType($type);
	}

	/**
	 *
	 * @param ColumnDescriptionInterface|TypeInterface $type
	 */
	public function setType($type)
	{
		if (!($type instanceof TypeInterface ||
			$type instanceof ColumnDescriptionInterface))
		{
			throw new \InvalidArgumentException(
				TypeInterface::class . ' or ' .
				ColumnDescriptionInterface::class . ' expected');
			$this->type = $type;
		}

		$this->type = $type;
	}

	public function tokenize(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$type = $this->type;
		$constraintFlags = 0;
		if ($type instanceof ColumnStructure)
		{
			$inspector = StructureInspector::getInstance();
			$constraintFlags = $inspector->getTableColumnConstraintFlags(
				$type);
		}

		if ($type instanceof ColumnDescriptionInterface)
		{
			if (!($type instanceof ColumnStructure))
			{
				if ($type instanceof ColumnDescriptionInterface)
				{
					$s = new ColumnStructure('runtime_type', null);
					foreach ($type as $key => $value)
						$s->setColumnProperty($key, $value);
					$type = $s;
				}
			}

			$type = $context->getPlatform()->getColumnType($type,
				$constraintFlags);
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
	 * @var ColumnDescriptionInterface|TypeInterface
	 */
	private $type;
}