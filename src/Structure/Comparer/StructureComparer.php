<?php

/**
 * Copyright © 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure\Comparer;

use NoreSources\Bitset;
use NoreSources\ComparableInterface;
use NoreSources\SingletonTrait;
use NoreSources\Container\Container;
use NoreSources\Expression\ExpressionInterface;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DataTypeDescription;
use NoreSources\SQL\DBMS\Reference\ReferencePlatform;
use NoreSources\SQL\Structure\CheckTableConstraint;
use NoreSources\SQL\Structure\ColumnStructure;
use NoreSources\SQL\Structure\ForeignKeyTableConstraint;
use NoreSources\SQL\Structure\IndexStructure;
use NoreSources\SQL\Structure\KeyTableConstraintInterface;
use NoreSources\SQL\Structure\NamespaceStructure;
use NoreSources\SQL\Structure\PrimaryKeyTableConstraint;
use NoreSources\SQL\Structure\StructureElementContainerInterface;
use NoreSources\SQL\Structure\StructureElementInterface;
use NoreSources\SQL\Structure\TableConstraintInterface;
use NoreSources\SQL\Structure\TableStructure;
use NoreSources\SQL\Structure\UniqueTableConstraint;
use NoreSources\SQL\Structure\ViewStructure;
use NoreSources\SQL\Syntax\Evaluable;
use NoreSources\SQL\Syntax\Evaluator;
use NoreSources\SQL\Syntax\Statement\StatementBuilder;
use NoreSources\Type\TypeConversion;
use NoreSources\Type\TypeDescription;

class StructureComparer
{
	use SingletonTrait;

	/**
	 * Ignore differences occuring when an element name
	 * changed from "something" to "nothing".
	 *
	 * Some DBMS does not accept a name for several structure elements.
	 * (ex: Primary keys are always anonymous in MySQL)
	 */
	const IGNORE_RENAME_EMPTY = Bitset::BIT_01;

	public function __construct()
	{
		$this->comparerFlags = 0;
	}

	public function setFlags($flags)
	{
		$this->comparerFlags = $flags;
	}

	public function compare(StructureElementInterface $reference,
		StructureElementInterface $target, $differenceLimit = -1)
	{
		$referenceClass = TypeDescription::getName($reference);
		$targetClass = TypeDescription::getName($target);

		if ($referenceClass != $targetClass)
			throw new StructureComparerException(
				'Cannot compare object of different class (' .
				$referenceClass . ' and ' . $targetClass . ')');

		$differences = [];

		$method = 'compare' . TypeDescription::getLocalName($reference);
		if (\method_exists($this, $method))
		{
			$b = \call_user_func([
				$this,
				$method
			], $reference, $target);
			if (!\is_array($b))
				throw new \RuntimeException($method);
			$differences = \array_merge($differences, $b);
		}

		$differenceCount = Container::count($differences);

		if ($differenceLimit > 0 && $differenceCount >= $differenceLimit)
			return $differences;

		if ($reference instanceof StructureElementContainerInterface)
			return \array_merge($differences,
				$this->compareStructureElementContainers($reference,
					$target, $differenceLimit - $differenceCount));

		return $differences;
	}

	public function compareStructureElementContainers(
		StructureElementContainerInterface $reference,
		StructureElementContainerInterface $target,
		$differenceLimit = -1)
	{
		$differences = [];
		$referenceTypeChildren = self::perElementTypeMap($reference);
		$targetTypeChildren = self::perElementTypeMap($target);

		$referenceTypes = Container::keys($referenceTypeChildren);

		$targetTypes = Container::keys($targetTypeChildren);

		foreach ($referenceTypeChildren as $type => $referenceChildren)
		{
			$targetChildren = Container::keyValue($targetTypeChildren,
				$type, []);

			$result = $this->pairElements($referenceChildren,
				$targetChildren);

			foreach ($result[self::PAIRING_RENAMED] as $entry)
			{
				$differences[] = new StructureDifference(
					StructureDifference::RENAMED, $entry[0], $entry[1]);

				$differenceLimit--;
				if ($differenceLimit == 0)
					return $differences;
			}

			foreach ($result[self::PAIRING_CREATED] as $k => $entry)
			{

				$differences[] = new StructureDifference(
					StructureDifference::CREATED, null, $entry);
				$differenceLimit--;
				if ($differenceLimit == 0)
					return $differences;
			}

			foreach ($result[self::PAIRING_DROPPED] as $k => $entry)
			{
				$drops = [
					$entry
				];
				if ($entry instanceof StructureElementContainerInterface)
				{
					$drops = \array_merge($drops,
						self::getDroppableChildren($entry));
				}

				foreach ($drops as $drop)
				{
					$differences[] = new StructureDifference(
						StructureDifference::DROPPED, $drop);
					$differenceLimit--;
					if ($differenceLimit == 0)
						return $differences;
				}
			}

			foreach ($result[self::PAIRING_MATCH] as $entry)
			{
				$d = $this->compare($entry[0], $entry[1],
					$differenceLimit);

				$differenceCount = Container::count($d);
				$differences = \array_merge($differences, $d);
				if ($differenceLimit > 0 &&
					$differenceCount >= $differenceLimit)
					return $differences;
				$differenceLimit -= $differenceCount;
			}
		}

		$targetTypeChildren = Container::filter($targetTypeChildren,
			function ($k, $v) use ($referenceTypes) {
				return !\in_array($k, $referenceTypes);
			});

		foreach ($targetTypeChildren as $type => $children)
		{
			foreach ($children as $child)
			{
				$differences[] = new StructureDifference(
					StructureDifference::CREATED, null, $child);
				$differenceLimit--;
				if ($differenceLimit == 0)
					return $differences;
			}
		}

		return $differences;
	}

	public function pairElements($references = array(),
		$targets = array())
	{
		$result = [
			self::PAIRING_MATCH => [],
			self::PAIRING_RENAMED => [],
			self::PAIRING_CREATED => [],
			self::PAIRING_DROPPED => []
		];

		foreach ($references as $k => $r)
		{
			if (($t = Container::keyValue($targets, $k, null)))
				$result[self::PAIRING_MATCH][$k] = [
					$r,
					$t
				];
			else
				$result[self::PAIRING_DROPPED][$k] = $r;
		}

		foreach ($targets as $k => $t)
		{
			if (!Container::keyExists($references, $k))
				$result[self::PAIRING_CREATED][$k] = $t;
		}

		$anonymousCreated = Container::filter(
			$result[self::PAIRING_CREATED],
			function ($k, $e) {
				return (\strlen($e->getName()) == 0);
			});

		$anonymousDropped = Container::filter(
			$result[self::PAIRING_DROPPED],
			function ($k, $e) {
				return (\strlen($e->getName()) == 0);
			});

		if (Container::count($anonymousCreated) == 1 &&
			Container::count($anonymousDropped) == 1)
		{
			$r = Container::firstValue($anonymousDropped);
			$dk = $r->getElementKey();
			$t = Container::firstValue($anonymousCreated);
			$ck = $t->getElementKey();
			$result[self::PAIRING_MATCH][$dk] = [
				$r,
				$t
			];
			unset($result[self::PAIRING_DROPPED][$dk]);
			unset($result[self::PAIRING_CREATED][$ck]);
		}

		$droppedKeys = Container::keys($result[self::PAIRING_DROPPED]);
		$createdKeys = Container::keys($result[self::PAIRING_CREATED]);

		foreach ($droppedKeys as $dk)
		{
			$r = $result[self::PAIRING_DROPPED][$dk];

			foreach ($createdKeys as $ck)
			{
				if (!Container::keyExists(
					$result[self::PAIRING_CREATED], $ck))
					continue;

				$t = $result[self::PAIRING_CREATED][$ck];
				$d = $this->compare($r, $t, 1);
				if (\count($d) == 0)
				{

					/**
					 *
					 * @todo Shall we ignore this here or later
					 *       or with config flag (IGNORE EMPTY_RENAME)
					 */
					$category = self::PAIRING_RENAMED;
					if ((empty($r->getName()) && empty($t->getName())))
						$category = self::PAIRING_MATCH;
					elseif (empty($t->getName()) &&
						($this->comparerFlags & self::IGNORE_RENAME_EMPTY))
						$category = self::PAIRING_MATCH;

					$result[$category][$dk] = [
						$r,
						$t
					];
					unset($result[self::PAIRING_DROPPED][$dk]);
					unset($result[self::PAIRING_CREATED][$ck]);
					break;
				}
			}
		}

		return $result;
	}

	public function compareColumnStructure(ColumnStructure $a,
		ColumnStructure $b, $limit = -1)
	{
		foreach ($a as $key => $pa)
		{
			if ($key == K::COLUMN_NAME)
				continue;

			if (!$b->has($key))
				return self::singleAlteredDifference($a, $b, '-' . $key);
			$pb = $b->get($key);

			if ($key == K::COLUMN_DATA_TYPE)
			{
				$c = $this->compareDataType($pa, $pb);
				if ($c != 0)
					return self::singleAlteredDifference($a, $b, $key);
				continue;
			}

			if ($pa instanceof ComparableInterface &&
				$pb instanceof ComparableInterface)
			{
				$c = $pa->compare($pb);
				if ($c != 0)
					return self::singleAlteredDifference($a, $b, $key);
				continue;
			}

			if ($pa != $pb)
				return self::singleAlteredDifference($a, $b, $key);
		}

		return [];
	}

	public function compareDataType($a, $b)
	{
		$description = DataTypeDescription::getInstance();
		$va = $description->getAffinities($a);
		$vb = $description->getAffinities($b);

		if (Container::valueExists($va, K::DATATYPE_NULL))
		{
			if (!Container::valueExists($vb, K::DATATYPE_NULL))
				return 1;
		}
		else
			if (Container::valueExists($vb, K::DATATYPE_NULL))
				return -1;

		$va = \array_diff($va, [
			K::DATATYPE_NULL
		]);
		$vb = \array_diff($vb, [
			K::DATATYPE_NULL
		]);
		$intersection = \array_intersect($va, $vb);

		return \count($intersection) ? 0 : (\count($va) - \count($vb));
	}

	public function compareUniqueTableConstraint(
		UniqueTableConstraint $a, UniqueTableConstraint $b, $limit = -1)
	{
		$d = $this->compareTableConstraintInterface($a, $b, $limit);
		if (Container::count($d))
			return $d;

		if (self::compareExpressions($a->getConstraintExpression(),
			$b->getConstraintExpression()) != 0)
			return self::singleAlteredDifference($a, $b);

		return [];
	}

	public function compareCheckTableConstraint(CheckTableConstraint $a,
		CheckTableConstraint $b, $limit = -1)
	{
		$d = $this->compareTableConstraintInterface($a, $b, $limit);
		if (Container::count($d))
			return $d;

		if (self::compareExpressions($a->getConstraintExpression(),
			$b->getConstraintExpression()) != 0)
			return self::singleAlteredDifference($a, $b);

		return [];
	}

	public function compareForeignKeyTableConstraint(
		ForeignKeyTableConstraint $a, ForeignKeyTableConstraint $b,
		$limit = -1)
	{
		$d = $this->compareTableConstraintInterface($a, $b, $limit);
		if (Container::count($d))
			return $d;

		if ($a->getForeignTable() != $b->getForeignTable())
			return self::singleAlteredDifference($a, $b);

		$ac = $a->getColumns();
		$bc = $b->getColumns();

		if (Container::count($ac) != Container::count($bc))
			return self::singleAlteredDifference($a, $b);

		foreach ($ac as $r => $t)
		{
			$bt = Container::keyValue($bc, $r, false);
			if ($bt != $t)
				return self::singleAlteredDifference($a, $b);
		}

		return [];
	}

	public function compareIndexStructure(IndexStructure $a,
		IndexStructure $b, $limit = -1)
	{
		if ($a->getIndexFlags() != $b->getIndexFlags())
			return self::singleAlteredDifference($a, $b);

		$ac = $a->getColumns();
		$bc = $b->getColumns();

		if (Container::count($ac) != Container::count($bc))
			return self::singleAlteredDifference($a, $b);

		foreach ($ac as $name)
		{
			if (!Container::valueExists($bc, $name))
				return self::singleAlteredDifference($a, $b);
		}

		if (self::compareExpressions($a->getConstraintExpression(),
			$b->getConstraintExpression()) != 0)
			return self::singleAlteredDifference($a, $b);
		return [];
	}

	public function comparePrimaryKeyTableConstraint(
		PrimaryKeyTableConstraint $a, PrimaryKeyTableConstraint $b,
		$limit = -1)
	{
		return $this->compareKeyTableConstraintInterface($a, $b, $limit);
	}

	public function compareKeyTableConstraintInterface(
		KeyTableConstraintInterface $a, KeyTableConstraintInterface $b,
		$limit = -1)
	{
		$d = $this->compareTableConstraintInterface($a, $b, $limit);
		if (Container::count($d))
			return $d;

		$ac = $a->getColumns();
		$bc = $b->getColumns();

		if (Container::count($ac) != Container::count($bc))
			return self::singleAlteredDifference($a, $b);

		foreach ($ac as $name)
		{
			if (!Container::valueExists($bc, $name))
				return self::singleAlteredDifference($a, $b);
		}

		if (!self::isSameExpression($a->getConstraintExpression(),
			$b->getConstraintExpression()))
			return self::singleAlteredDifference($a, $b);

		if ($a->getConstraintExpression())
		{
			if (!$b->getConstraintExpression())
				return self::singleAlteredDifference($a, $b);
		/**
		 *
		 * @todo Compare expression
		 */
		}
		elseif ($b->getConstraintExpression())
			return self::singleAlteredDifference($a, $b);

		return [];
	}

	public function compareTableConstraintInterface(
		TableConstraintInterface $a, TableConstraintInterface $b,
		$limit = -1)
	{
		if ($a->getConstraintFlags() != $b->getConstraintFlags())
			return self::singleAlteredDifference($a, $b);
		return [];
	}

	/**
	 *
	 * @param Evaluable $ae
	 * @param Evaluable $be
	 * @return number Text difference
	 */
	protected static function compareExpressions($ae = null, $be = null)
	{
		if ($ae)
		{
			if ($be)
			{
				$builder = StatementBuilder::getInstance();
				$evaluator = Evaluator::getInstance();
				$platform = new ReferencePlatform();
				$sa = $builder($evaluator($ae), $platform);
				$sb = $builder($evaluator($be), $platform);
				return \strcmp(TypeConversion::toString($sa),
					TypeConversion::toString($sb));
			}

			return 1;
		}

		if ($be)
			return -1;

		return 0;
	}

	public static function perElementTypeMap(
		StructureElementContainerInterface $container)
	{
		$perTypes = [];
		foreach ($container as $element)
		{
			/** @var StructureElementInterface $element */
			$className = TypeDescription::getName($element);
			if (!Container::keyExists($perTypes, $className))
				$perTypes[$className] = [];

			$perTypes[$className][$element->getElementKey()] = $element;
		}

		return $perTypes;
	}

	/**
	 *
	 * @param ExpressionInterface $a
	 * @param ExpressionInterface $b
	 * @return boolean
	 */
	protected static function isSameExpression($a, $b)
	{
		if ($a)
		{
			if (!$b)
				return false;
		}
		elseif ($b)
			return false;
		return true;
	}

	protected static function singleAlteredDifference($a, $b, $hint = '')
	{
		return [
			new StructureDifference(StructureDifference::ALTERED, $a, $b,
				$hint)
		];
	}

	protected static function getDroppableChildren(
		StructureElementContainerInterface $container)
	{
		static $droppable = [
			NamespaceStructure::class,
			TableStructure::class,
			ViewStructure::class,
			IndexStructure::class
		];
		$list = [];
		foreach ($container as $element)
		{
			if ($element instanceof StructureElementContainerInterface)
			{
				$list = \array_merge($list,
					self::getDroppableChildren($element));
			}

			if (\in_array(\get_class($element), $droppable))
			{
				$list[] = $element;
			}
		}
		return $list;
	}

	private $comparerFlags;

	const PAIRING_MATCH = 'matching';

	const PAIRING_RENAMED = StructureDifference::RENAMED;

	const PAIRING_CREATED = StructureDifference::CREATED;

	const PAIRING_DROPPED = StructureDifference::DROPPED;
}
