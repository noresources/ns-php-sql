<?php

/**
 * Copyright © 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure\Comparer;

use NoreSources\Bitset;
use NoreSources\ComparisonException;
use NoreSources\SingletonTrait;
use NoreSources\Container\Container;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DataTypeDescription;
use NoreSources\SQL\DBMS\Reference\ReferencePlatform;
use NoreSources\SQL\Structure\CheckTableConstraint;
use NoreSources\SQL\Structure\ColumnStructure;
use NoreSources\SQL\Structure\DatasourceStructure;
use NoreSources\SQL\Structure\ForeignKeyTableConstraint;
use NoreSources\SQL\Structure\IndexStructure;
use NoreSources\SQL\Structure\KeyTableConstraintInterface;
use NoreSources\SQL\Structure\NamespaceStructure;
use NoreSources\SQL\Structure\PrimaryKeyTableConstraint;
use NoreSources\SQL\Structure\Structure;
use NoreSources\SQL\Structure\StructureElementContainerInterface;
use NoreSources\SQL\Structure\StructureElementInterface;
use NoreSources\SQL\Structure\StructureResolver;
use NoreSources\SQL\Structure\TableConstraintInterface;
use NoreSources\SQL\Structure\TableStructure;
use NoreSources\SQL\Structure\UniqueTableConstraint;
use NoreSources\SQL\Structure\ViewStructure;
use NoreSources\SQL\Syntax\Evaluable;
use NoreSources\SQL\Syntax\Evaluator;
use NoreSources\SQL\Syntax\Statement\StatementBuilder;
use NoreSources\Type\TypeComparison;
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

	/**
	 * Invoke the compare() method
	 *
	 * @return StructureDifference[]
	 */
	public function __invoke()
	{
		return \call_user_func_array([
			$this,
			'compare'
		], func_get_args());
	}

	/**
	 * Compare two structure elements
	 *
	 * @param StructureElementInterface $reference
	 * @param StructureElementInterface $target
	 * @throws StructureComparerException
	 * @return StructureDifference[]
	 */
	public function compare(StructureElementInterface $reference,
		StructureElementInterface $target)
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
			$d = \call_user_func([
				$this,
				$method
			], $reference, $target);
			if (!\is_array($d))
				throw new \RuntimeException($method);
			if (\count($d))
				$differences = \array_merge($differences, $d);
		}

		if ($reference instanceof StructureElementContainerInterface)
			return \array_merge($differences,
				$this->compareStructureElementContainers($reference,
					$target));

		return $differences;
	}

	public function compareStructureElementContainers(
		StructureElementContainerInterface $reference,
		StructureElementContainerInterface $target)
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
			}

			foreach ($result[self::PAIRING_CREATED] as $k => $entry)
			{

				$differences[] = new StructureDifference(
					StructureDifference::CREATED, null, $entry);
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
				}
			}

			foreach ($result[self::PAIRING_MATCH] as $entry)
			{
				$d = $this->compare($entry[0], $entry[1]);
				$differences = \array_merge($differences, $d);
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
		ColumnStructure $b)
	{
		$d = null;

		$strict = $this->canStrictCompare($a, $b);

		// Length and scale
		$scaleWithoutLengthMismatch = false;
		if (!$strict &&
			($da = Container::keyValue($a, K::COLUMN_DATA_TYPE)) &&
			($db = Container::keyValue($b, K::COLUMN_DATA_TYPE)) &&
			($da & K::DATATYPE_REAL) && ($db & K::DATATYPE_REAL) &&
			($sa = Container::keyValue($a, K::COLUMN_FRACTION_SCALE)) &&
			($sb = Container::keyValue($b, K::COLUMN_FRACTION_SCALE)) &&
			($sa == $sb))
		{
			$scaleWithoutLengthMismatch = $a->has(K::COLUMN_LENGTH) !=
				$b->has(K::COLUMN_LENGTH);
		}

		foreach ($a as $key => $pa)
		{
			if ($key == K::COLUMN_NAME)
				continue;
			elseif ($scaleWithoutLengthMismatch &&
				($key == K::COLUMN_LENGTH ||
				$key == K::COLUMN_FRACTION_SCALE))
				continue;

			// default

			if (!$b->has($key))
			{
				$d = self::alterDifference($d, $a, $b,
					[
						DifferenceExtra::KEY_TYPE => $key,
						DifferenceExtra::KEY_PREVIOUS => $pa
					]);
				continue;
			}
			$pb = $b->get($key);

			if ($key == K::COLUMN_DATA_TYPE)
			{
				$c = $this->compareDataType($pa, $pb);
				if ($c != 0)
				{
					$d = self::alterDifference($d, $a, $b,
						[
							DifferenceExtra::KEY_TYPE => $key,
							DifferenceExtra::KEY_PREVIOUS => $pa,
							DifferenceExtra::KEY_NEW => $pb
						]);
				}
				continue;
			}

			$pd = 0;
			try
			{
				$pd = TypeComparison::compare($pa, $pb);
			}
			catch (ComparisonException $e)
			{
				$pd = ($pa != $pb) ? -1 : 0;
			}

			if ($pd != 0)
			{
				$d = self::alterDifference($d, $a, $b,
					[
						DifferenceExtra::KEY_TYPE => $key,
						DifferenceExtra::KEY_PREVIOUS => $pa,
						DifferenceExtra::KEY_NEW => $pb
					]);
			}
		}

		foreach ($b as $key => $pb)
		{
			if ($a->has($key))
				continue;
			$d = self::alterDifference($d, $a, $b,
				[
					DifferenceExtra::KEY_TYPE => $key,
					DifferenceExtra::KEY_NEW => $pb
				]);
		}

		return $d ? [
			$d
		] : [];
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
		UniqueTableConstraint $a, UniqueTableConstraint $b)
	{
		$d = null;
		$d = $this->populateTableConstraintInterfaceDifferences($a, $b,
			$d);

		$d = self::populateColumnNameListDifferences($a,
			$a->getParentElement(), $a->getColumns(), $b,
			$b->getParentElement(), $b->getColumns(), $d);

		if (self::compareExpressions($a->getConstraintExpression(),
			$b->getConstraintExpression()) != 0)
			$d = self::alterDifference($d, $a, $b,
				[
					DifferenceExtra::KEY_TYPE => DifferenceExtra::TYPE_EXPRESSION,
					DifferenceExtra::KEY_PREVIOUS => $a->getConstraintExpression(),
					DifferenceExtra::KEY_NEW => $b->getConstraintExpression()
				]);

		return $d ? [
			$d
		] : [];
	}

	public function compareCheckTableConstraint(CheckTableConstraint $a,
		CheckTableConstraint $b)
	{
		$d = null;
		$d = $this->populateTableConstraintInterfaceDifferences($a, $b,
			$d);

		if (self::compareExpressions($a->getConstraintExpression(),
			$b->getConstraintExpression()) != 0)
			$d = self::alterDifference($d, $a, $b,
				[
					DifferenceExtra::KEY_TYPE => DifferenceExtra::TYPE_EXPRESSION,
					DifferenceExtra::KEY_PREVIOUS => $a->getConstraintExpression(),
					DifferenceExtra::KEY_NEW => $b->getConstraintExpression()
				]);

		return $d ? [
			$d
		] : [];
	}

	public function compareForeignKeyTableConstraint(
		ForeignKeyTableConstraint $a, ForeignKeyTableConstraint $b)
	{
		$d = null;
		$d = $this->populateTableConstraintInterfaceDifferences($a, $b,
			$d);

		$resolver = new StructureResolver();
		$resolver->setPivot($a->getParentElement());
		$fta = $resolver->findTable($a->getForeignTable());
		$ftb = $resolver->findTable($b->getForeignTable());

		$fc = $fta->getIdentifier()->compare($ftb->getIdentifier());
		if ($fc != 0)
		{
			$d = self::alterDifference($d, $a, $a,
				[
					DifferenceExtra::KEY_TYPE => DifferenceExtra::TYPE_FOREIGN_TABLE,
					DifferenceExtra::KEY_PREVIOUS => $fta,
					DifferenceExtra::KEY_NEW => $ftb
				]);
		}

		$ca = $a->getColumns();
		$cb = $b->getColumns();

		$d = self::populateColumnNameListDifferences($a,
			$a->getParentElement(), Container::keys($ca), $b,
			$b->getParentElement(), Container::keys($cb), $d);

		$d = self::populateColumnNameListDifferences($a,
			$a->getParentElement(), Container::values($ca), $b,
			$b->getParentElement(), Container::values($cb), $d,
			DifferenceExtra::TYPE_FOREIGN_COLUMN);
		return $d ? [
			$d
		] : [];
	}

	public function compareIndexStructure(IndexStructure $a,
		IndexStructure $b)
	{
		$d = null;
		if ($a->getIndexFlags() != $b->getIndexFlags())
			$d = self::alterDifference($d, $a, $b,
				[
					DifferenceExtra::KEY_TYPE => DifferenceExtra::TYPE_FLAGS,
					DifferenceExtra::KEY_PREVIOUS => $a->getIndexFlags(),
					DifferenceExtra::KEY_NEW => $b->getIndexFlags()
				]);

		$d = self::populateColumnNameListDifferences($a,
			$a->getParentElement(), $a->getColumns(), $b,
			$b->getParentElement(), $b->getColumns(), $d);

		if (self::compareExpressions($a->getConstraintExpression(),
			$b->getConstraintExpression()) != 0)
			$d = self::alterDifference($d, $a, $b,
				[
					DifferenceExtra::KEY_TYPE => DifferenceExtra::TYPE_EXPRESSION,
					DifferenceExtra::KEY_PREVIOUS => $a->getConstraintExpression(),
					DifferenceExtra::KEY_NEW => $b->getConstraintExpression()
				]);
		return ($d) ? [
			$d
		] : [];
	}

	public function comparePrimaryKeyTableConstraint(
		PrimaryKeyTableConstraint $a, PrimaryKeyTableConstraint $b)
	{
		return $this->compareKeyTableConstraintInterface($a, $b);
	}

	public function compareKeyTableConstraintInterface(
		KeyTableConstraintInterface $a, KeyTableConstraintInterface $b)
	{
		$d = null;
		$d = $this->populateTableConstraintInterfaceDifferences($a, $b,
			$d);

		$d = self::populateColumnNameListDifferences($a,
			$a->getParentElement(), $a->getColumns(), $b,
			$b->getParentElement(), $b->getColumns(), $d);

		if (self::compareExpressions($a->getConstraintExpression(),
			$b->getConstraintExpression()) != 0)
			$d = self::alterDifference($d, $a, $b,
				[
					DifferenceExtra::KEY_TYPE => DifferenceExtra::TYPE_EXPRESSION,
					DifferenceExtra::KEY_PREVIOUS => $a->getConstraintExpression(),
					DifferenceExtra::KEY_NEW => $b->getConstraintExpression()
				]);

		return $d ? [
			$d
		] : [];
	}

	public function populateTableConstraintInterfaceDifferences(
		TableConstraintInterface $a, TableConstraintInterface $b,
		StructureDifference &$existing = null)
	{
		if ($a->getConstraintFlags() != $b->getConstraintFlags())
			$existing = self::alterDifference($d, $a, $b,
				[
					DifferenceExtra::KEY_TYPE => DifferenceExtra::TYPE_FLAGS,
					DifferenceExtra::KEY_PREVIOUS => $a->getConstraintFlags(),
					DifferenceExtra::KEY_NEW => $b->getConstraintFlags()
				]);
		return $existing;
	}

	/**
	 *
	 * @param StructureDifference $existing
	 *        	Main StructureDifference
	 * @param StructureElementInterface $a
	 *        	Reference
	 * @param TableStructure $ta
	 *        	Reference table
	 * @param unknown $ca
	 *        	Reference table column name list
	 * @param StructureElementInterface $b
	 *        	Target
	 * @param TableStructure $tb
	 *        	Target table
	 * @param unknown $cb
	 *        	Target table column name list
	 * @return \NoreSources\SQL\Structure\Comparer\StructureDifference A reference to $existing
	 */
	protected function populateColumnNameListDifferences(
		StructureElementInterface $a, TableStructure $ta, $ca,
		StructureElementInterface $b, TableStructure $tb, $cb,
		StructureDifference &$existing = null,
		$type = DifferenceExtra::TYPE_COLUMN)
	{
		foreach ($ca as $c)
		{
			if (!Container::valueExists($cb, $c))
				$existing = self::alterDifference($existing, $a, $b,
					[
						DifferenceExtra::KEY_TYPE => $type,
						DifferenceExtra::KEY_PREVIOUS => $ta[$c]
					]);
		}

		foreach ($cb as $c)
		{
			if (!Container::valueExists($ca, $c))
				$existing = self::alterDifference($existing, $a, $b,
					[
						DifferenceExtra::KEY_TYPE => $type,
						DifferenceExtra::KEY_NEW => $tb[$c]
					]);
		}

		return $existing;
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

	protected static function canStrictCompare(
		StructureElementInterface $a, StructureElementInterface $b)
	{
		$ra = Structure::getRootElement($a);
		$rb = Structure::getRootElement($b);

		$ca = null;
		if ($ra instanceof DatasourceStructure &&
			$ra->getMetadata()->has(K::STRUCTURE_METADATA_CONNECTION))
		{
			$ca = $ra->getMetadata()->get(
				K::STRUCTURE_METADATA_CONNECTION);
		}
		$cb = null;
		if ($rb instanceof DatasourceStructure &&
			$rb->getMetadata()->has(K::STRUCTURE_METADATA_CONNECTION))
		{
			$cb = $rb->getMetadata()->get(
				K::STRUCTURE_METADATA_CONNECTION);
		}

		if ($ca)
		{
			if ($cb)
				return $ca === $cb;
			return false;
		}
		elseif ($cb)
			return false;

		return true;
	}

	protected static function alterDifference(
		StructureDifference $existing = null,
		StructureElementInterface $a = null,
		StructureElementInterface $b = null, $extra = null)
	{
		if (!isset($existing))
			$existing = new StructureDifference(
				StructureDifference::ALTERED, $a, $b);
		if (\is_array($extra))
			$extra = new DifferenceExtra($extra);
		if ($extra instanceof DifferenceExtra)
			$existing->appendExtra($extra);
		return $existing;
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
