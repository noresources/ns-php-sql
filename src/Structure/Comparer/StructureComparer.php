<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Structure\Comparer;

use NoreSources\ComparisonException;
use NoreSources\SingletonTrait;
use NoreSources\Container\Container;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DataDescription;
use NoreSources\SQL\DataTypeDescription;
use NoreSources\SQL\DBMS\ConnectionInterface;
use NoreSources\SQL\DBMS\Reference\ReferencePlatform;
use NoreSources\SQL\Structure\CheckTableConstraint;
use NoreSources\SQL\Structure\ColumnStructure;
use NoreSources\SQL\Structure\DatasourceStructure;
use NoreSources\SQL\Structure\ForeignKeyTableConstraint;
use NoreSources\SQL\Structure\Identifier;
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
use NoreSources\SQL\Structure\Inspector\StructureInspector;
use NoreSources\SQL\Syntax\Evaluable;
use NoreSources\SQL\Syntax\Evaluator;
use NoreSources\SQL\Syntax\Statement\StatementBuilder;
use NoreSources\Type\TypeComparison;
use NoreSources\Type\TypeConversion;
use NoreSources\Type\TypeDescription;

/**
 * Compare two structure and report differences
 */
class StructureComparer
{
	use SingletonTrait;

	/**
	 * Invoke the compare() method
	 *
	 * @return StructureComparison[]
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
	 * @return StructureComparison[]
	 */
	public function compare(StructureElementInterface $reference,
		StructureElementInterface $target,
		$typeFlags = StructureComparison::DIFFERENCE_TYPES)
	{
		$comparisons = $this->mainCompare($reference, $target,
			$typeFlags);
		return $this->pairDifferenceExtras($comparisons, $reference,
			$target);
	}

	public function compareStructureElementContainers(
		StructureElementContainerInterface $reference,
		StructureElementContainerInterface $target,
		$typeFlags = StructureComparison::DIFFERENCE_TYPES)
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
				$differences[] = new StructureComparison(
					StructureComparison::RENAMED, $entry[0], $entry[1]);
			}

			foreach ($result[self::PAIRING_CREATED] as $k => $entry)
			{

				$differences[] = new StructureComparison(
					StructureComparison::CREATED, null, $entry);
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
					$differences[] = new StructureComparison(
						StructureComparison::DROPPED, $drop);
				}
			}

			foreach ($result[self::PAIRING_MATCH] as $entry)
			{
				$d = $this->mainCompare($entry[0], $entry[1], $typeFlags);
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
				$differences[] = new StructureComparison(
					StructureComparison::CREATED, null, $child);
			}
		}

		return $differences;
	}

	/**
	 *
	 * @param array $references
	 * @param array $targets
	 * @return array[]|mixed[]|unknown[]|unknown[][]|\Iterator[][]|mixed[][]|NULL[][]|array[][]|\ArrayAccess[][]|\Psr\Container\ContainerInterface[][]|\Traversable[][]
	 */
	public function pairElements($references, $targets)
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
				$d = $this->mainCompare($r, $t);
				if (\count($d) == 0)
				{
					$category = self::PAIRING_RENAMED;
					if (empty($r->getName()))
					{
						if (empty($t->getName()))
							$category = self::PAIRING_MATCH;
						elseif (self::isFromConnection($r))
							$category = self::PAIRING_MATCH;
					}
					elseif (empty($t->getName()) &&
						self::isFromConnection($t))
					{
						$category = self::PAIRING_MATCH;
					}

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
		$excludes = [
			K::COLUMN_NAME
		];

		static $customComparer = [
			K::COLUMN_DEFAULT_VALUE => 'compareDefaultValues',
			K::COLUMN_DATA_TYPE => 'compareDataType',
			K::COLUMN_LENGTH => 'compareLength',
			K::COLUMN_FLAGS => 'compareColumnFlags'
		];

		foreach ($a as $key => $pa)
		{
			if (Container::valueExists($excludes, $key))
				continue;
			elseif (($cf = Container::keyValue($customComparer, $key)))
			{
				$c = \call_user_func([
					$this,
					$cf
				], $a, $b, $strict);
				if ($c != 0)
				{
					$diff = [
						DifferenceExtra::KEY_TYPE => $key,
						DifferenceExtra::KEY_PREVIOUS => $pa
					];
					if ($b->has($key))
						$diff[DifferenceExtra::KEY_NEW] = $b->get($key);
					$d = self::alterDifference($d, $a, $b, $diff);
				}
				continue;
			}

			if (!$b->has($key))
			{
				if (!$strict &&
					$this->canIgnoreMissingColumnProperty($a, $b, $key))
					continue;

				$d = self::alterDifference($d, $a, $b,
					[
						DifferenceExtra::KEY_TYPE => $key,
						DifferenceExtra::KEY_PREVIOUS => $pa
					]);
				continue;
			}

			// default

			$pb = $b->get($key);
			$pd = $this->compareValues($pa, $pb);

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
			if (Container::valueExists($excludes, $key) || $a->has($key))
				continue;
			elseif (($cf = Container::keyValue($customComparer, $key)))
			{
				$c = \call_user_func([
					$this,
					$cf
				], $b, $a, $strict);
				if ($c != 0)
				{
					$d = self::alterDifference($d, $a, $b,
						[
							DifferenceExtra::KEY_TYPE => $key,
							DifferenceExtra::KEY_NEW => $b->get($key)
						]);
				}
				continue;
			}

			if (!$strict &&
				$this->canIgnoreMissingColumnProperty($b, $a, $key))
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

	public function compareValues($a, $b, $strict = true)
	{
		$d = 0;
		if ($strict)
		{
			try
			{
				$d = TypeComparison::compare($a, $b);
			}
			catch (ComparisonException $e)
			{
				$d = ($a != $b) ? -1 : 0;
			}
		}
		else
		{
			$dd = DataDescription::getInstance();
			$d = $dd->isSimilar($a, $b) ? 0 : 1;
		}

		return $d;
	}

	public function compareDefaultValues(ColumnStructure $a,
		ColumnStructure $b, $strict = true)
	{
		$aDataType = Container::keyValue($a, K::COLUMN_DATA_TYPE);
		$bDataType = Container::keyValue($b, K::COLUMN_DATA_TYPE);

		$dd = DataDescription::getInstance();

		if ($a->has(K::COLUMN_DEFAULT_VALUE))
		{
			$pa = $a->get(K::COLUMN_DEFAULT_VALUE);
			if ($b->has(K::COLUMN_DEFAULT_VALUE))
			{
				return $this->compareValues($pa,
					$b->get(K::COLUMN_DEFAULT_VALUE), $strict);
			}

			if (($bDataType & K::DATATYPE_NULL) && $dd->isNull($pa))
				return 0;

			return 1;
		}
		elseif ($b->has(K::COLUMN_DEFAULT_VALUE))
		{
			if (($aDataType & K::DATATYPE_NULL) &&
				$dd->isNull($b->get(K::COLUMN_DEFAULT_VALUE)))
				return 0;

			return -1;
		}

		return 0;
	}

	public function compareDataType(StructureElementInterface $a,
		StructureElementInterface $b, $strict = true)
	{
		$description = DataTypeDescription::getInstance();
		$inspector = StructureInspector::getInstance();

		$aDataType = Container::keyValue($a, K::COLUMN_DATA_TYPE,
			K::DATATYPE_UNDEFINED);
		$bDataType = Container::keyValue($b, K::COLUMN_DATA_TYPE,
			K::DATATYPE_UNDEFINED);

		if ($aDataType == $bDataType)
			return 0;

		$va = $description->getAffinities($aDataType);
		$vb = $description->getAffinities($bDataType);

		if ($aDataType & K::DATATYPE_NULL)
		{
			if (($bDataType & K::DATATYPE_NULL) == 0)
			{
				$flags = ($a instanceof ColumnStructure) ? $inspector->getTableColumnConstraintFlags(
					$a) : 0;
				if (($flags & K::CONSTRAINT_COLUMN_PRIMARY_KEY) !=
					K::CONSTRAINT_COLUMN_PRIMARY_KEY)
					return 1;
			}
		}
		elseif ($bDataType & K::DATATYPE_NULL)
		{
			$flags = ($b instanceof ColumnStructure) ? $inspector->getTableColumnConstraintFlags(
				$b) : 0;
			if (($flags & K::CONSTRAINT_COLUMN_PRIMARY_KEY) !=
				K::CONSTRAINT_COLUMN_PRIMARY_KEY)
				return -1;
		}

		$va = \array_diff($va, [
			K::DATATYPE_NULL
		]);
		$vb = \array_diff($vb, [
			K::DATATYPE_NULL
		]);

		$aDataType &= ~K::DATATYPE_NULL;
		$bDataType &= ~K::DATATYPE_NULL;

		// integer(1) -=> bool
		if (!$strict)
		{
			if (($aDataType == K::DATATYPE_BOOLEAN &&
				$this->isBooleanCompatibleType($b)) ||
				($bDataType == K::DATATYPE_BOOLEAN &&
				$this->isBooleanCompatibleType($a)))
				return 0;
		}

		$intersection = \array_intersect($va, $vb);

		return \count($intersection) ? 0 : (\count($va) - \count($vb));
	}

	public function compareColumnFlags(StructureElementInterface $a,
		StructureElementInterface $b, $strict = true)
	{
		$fa = Container::keyValue($a, K::COLUMN_FLAGS, 0);
		$fb = Container::keyValue($b, K::COLUMN_FLAGS, 0);

		$v = $fa - $fb;
		if ($v == 0 || $strict)
			return $v;

		$a = Structure::getRootElement($a);
		$b = Structure::getRootElement($b);
		$ma = ($a instanceof DatasourceStructure) ? $a->getMetadata() : null;
		$mb = ($b instanceof DatasourceStructure) ? $b->getMetadata() : null;
		$ca = null;
		if ($ma && $ma->has(K::STRUCTURE_METADATA_CONNECTION))
			$ca = $ma->get(K::STRUCTURE_METADATA_CONNECTION);
		$cb = null;
		if ($mb && $mb->has(K::STRUCTURE_METADATA_CONNECTION))
			$cb = $mb->get(K::STRUCTURE_METADATA_CONNECTION);

		if ($ca)
			$fa &= ~(K::COLUMN_FLAG_UNSIGNED);
		if ($cb)
			$fa &= ~(K::COLUMN_FLAG_UNSIGNED);

		return ($fa - $fb);
	}

	public function compareLength(StructureElementInterface $a,
		StructureElementInterface $b, $strict = true)
	{
		$aIsEnum = Container::keyExists($a, K::COLUMN_ENUMERATION);
		$bIsEnum = Container::keyExists($b, K::COLUMN_ENUMERATION);

		if ($aIsEnum || $bIsEnum)
			return 0;

		if ($a->has(K::COLUMN_LENGTH))
		{
			$pa = $a->get(K::COLUMN_LENGTH);
			if ($b->has(K::COLUMN_LENGTH))
				return $this->compareValues($pa,
					$b->get(K::COLUMN_LENGTH));

			if (!$strict)
			{
				/**
				 * (1) In DBMS, scale requires a length
				 * whereas noresource SQL schema may only specify scale.
				 */
				if ($a->has(K::COLUMN_FRACTION_SCALE) &&
					$b->has(K::COLUMN_FRACTION_SCALE))
					return 0;

				/**
				 * (2) integer(1) <=> boolean
				 */
				if (Container::keyValue($b, K::COLUMN_DATA_TYPE) &
					K::DATATYPE_BOOLEAN &&
					$this->isBooleanCompatibleType($a))
					return 0;

				/**
				 * (3) DBMS may require a length for key columns
				 */
				if (self::isKeyMandatoryLength($a))
					return 0;
			}
			return 1;
		}
		elseif ($b->has(K::COLUMN_LENGTH))
		{
			$pb = $b->get(K::COLUMN_LENGTH);
			if ($pb <= 0)
				return 0;
			if (!$strict)
			{
				// See (1) above
				if ($a->has(K::COLUMN_FRACTION_SCALE) &&
					$b->has(K::COLUMN_FRACTION_SCALE))
					return 0;

				if (Container::keyValue($a, K::COLUMN_DATA_TYPE) &
					K::DATATYPE_BOOLEAN &&
					$this->isBooleanCompatibleType($b))
					return 0;

				if (self::isKeyMandatoryLength($b))
					return 0;
			}
		}

		return 0;
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
		StructureComparison &$existing = null)
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
	 * integer(1) <=> boolean
	 *
	 * @param ColumnStructure $c
	 * @return boolean
	 */
	public function isBooleanCompatibleType(ColumnStructure $c)
	{
		return ((($dataType = Container::keyValue($c,
			K::COLUMN_DATA_TYPE)) & K::DATATYPE_BOOLEAN) ||
			(($dataType & K::DATATYPE_INTEGER) &&
			(Container::keyValue($c, K::COLUMN_LENGTH, 0) == 1) &&
			(Container::keyValue($c, K::COLUMN_FRACTION_SCALE, 0) == 0)));
	}

	/**
	 * Indicates if missing column property can be ignored on non-strict comparison
	 *
	 * @param ColumnStructure $a
	 *        	Column that have the property
	 * @param ColumnStructure $b
	 *        	Column that does not have the property
	 * @param string $key
	 *        	Property key
	 * @return boolean
	 */
	public function canIgnoreMissingColumnProperty(ColumnStructure $a,
		ColumnStructure $b, $key)
	{
		switch ($key)
		{
			case K::COLUMN_ENUMERATION:
				{

					$aType = Container::keyValue($a, K::COLUMN_DATA_TYPE,
						0);
					$aType &= ~K::DATATYPE_NULL;

					return ($aType == K::DATATYPE_INTEGER ||
						$aType == K::DATATYPE_STRING);
				}
			break;
		}
		return false;
	}

	protected function mainCompare(StructureElementInterface $reference,
		StructureElementInterface $target,
		$typeFlags = StructureComparison::DIFFERENCE_TYPES)
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
			$differences = \call_user_func([
				$this,
				$method
			], $reference, $target);
			if (!\is_array($differences))
				throw new \RuntimeException($method);
		}

		if ((\count($differences) == 0) &&
			(($typeFlags & StructureComparison::IDENTICAL) ==
			StructureComparison::IDENTICAL))
		{
			$differences[] = new StructureComparison(
				StructureComparison::IDENTICAL, $reference, $target);
		}

		if ($reference instanceof StructureElementContainerInterface)
			return \array_merge($differences,
				$this->compareStructureElementContainers($reference,
					$target, $typeFlags));

		return $differences;
	}

	/**
	 *
	 * @param StructureComparison[] $comparisons
	 * @return StructureComparison[]
	 */
	protected function pairDifferenceExtras(&$comparisons,
		StructureElementInterface $reference,
		StructureElementInterface $target)
	{
		$inspector = StructureInspector::getInstance();

		$renames = [];
		foreach ($comparisons as $comparison)
		{
			/** @var StructureComparison $comparison */
			if (!($comparison->getType() == StructureComparison::RENAMED))
				continue;

			$r = $comparison->getReference();
			$r = Identifier::make($r, false);
			$renames[\strval($r)] = $comparison->getTarget();
		}

		static $extraTypeFilter = [
			DifferenceExtra::TYPE_COLUMN,
			DifferenceExtra::TYPE_FOREIGN_COLUMN,
			DifferenceExtra::TYPE_FOREIGN_TABLE,
			DifferenceExtra::TYPE_TABLE
		];

		foreach ($comparisons as $comparison)
		{
			/** @var StructureComparison $comparison */
			if (!($comparison->getType() == StructureComparison::ALTERED))
				continue;
			$extras = $comparison->getExtras();
			$extraRenames = [];
			foreach ($extras as $extra)
			{
				/** @var DifferenceExtra $extra */
				if ($extra->has(DifferenceExtra::KEY_TYPE) &&
					($type = $extra->get(DifferenceExtra::KEY_TYPE)) &&
					Container::valueExists($extraTypeFilter, $type) &&
					$extra->has(DifferenceExtra::KEY_PREVIOUS) &&
					!$extra->has(DifferenceExtra::KEY_NEW) &&
					($previous = $extra->get(
						DifferenceExtra::KEY_PREVIOUS)) &&
					($previousKey = \strval(
						Identifier::make($previous, false))) &&
					($target = Container::keyValue($renames,
						$previousKey)))
				{
					$extra[DifferenceExtra::KEY_NEW] = $target;
					$targetKey = \strval(
						Identifier::make($target, false));
					$extraRenames[$targetKey] = $targetKey;
				}
			}

			if (\count($extraRenames) == 0)
				continue;

			$newExtras = [];
			foreach ($extras as $extra)
			{
				/** @var DifferenceExtra $extra */
				if ($extra->has(DifferenceExtra::KEY_TYPE) &&
					($type = $extra->get(DifferenceExtra::KEY_TYPE)) &&
					Container::valueExists($extraTypeFilter, $type) &&
					$extra->has(DifferenceExtra::KEY_NEW) &&
					!$extra->has(DifferenceExtra::KEY_PREVIOUS) &&
					($n = $extra->get(DifferenceExtra::KEY_NEW)) &&
					($nKey = \strval(Identifier::make($n, false))) &&
					Container::keyExists($extraRenames, $nKey))
				{
					continue;
				}
				$newExtras[] = $extra;
			}

			$comparison->exchangeExtras($newExtras);
		}

		return $comparisons;
	}

	/**
	 *
	 * @param StructureComparison $existing
	 *        	Main StructureComparison
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
	 * @return \NoreSources\SQL\Structure\Comparer\StructureComparison A reference to $existing
	 */
	protected function populateColumnNameListDifferences(
		StructureElementInterface $a, TableStructure $ta, $ca,
		StructureElementInterface $b, TableStructure $tb, $cb,
		StructureComparison &$existing = null,
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

	protected static function isFromConnection(
		StructureElementInterface $a)
	{
		return (($r = Structure::getRootElement($a)) &&
			($r instanceof DatasourceStructure) &&
			$r->getMetadata()->has(K::STRUCTURE_METADATA_CONNECTION));
	}

	protected static function isKeyMandatoryLength(ColumnStructure $a)
	{
		$inspector = StructureInspector::getInstance();
		$flags = $inspector->getTableColumnConstraintFlags($a);
		if (!($flags &
			(K::CONSTRAINT_COLUMN_KEY | K::CONSTRAINT_COLUMN_FOREIGN_KEY)))
			return false;

		/** @var DatasourceStructure $r */
		$r = Structure::getRootElement($a);
		if (!($r instanceof DatasourceStructure &&
			$r->getMetadata()->has(K::STRUCTURE_METADATA_CONNECTION)))
			return false;

		/** @var ConnectionInterface $c */
		$c = $r->getMetadata()->get(K::STRUCTURE_METADATA_CONNECTION);

		$platform = $c->getPlatform();
		$columnDeclaration = $platform->queryFeature(
			[
				K::FEATURE_CREATE,
				K::FEATURE_ELEMENT_TABLE,
				K::FEATURE_COLUMN_DECLARATION_FLAGS
			], 0);
		return ($columnDeclaration &
			K::FEATURE_COLUMN_KEY_MANDATORY_LENGTH) ==
			K::FEATURE_COLUMN_KEY_MANDATORY_LENGTH;
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
		StructureComparison $existing = null,
		StructureElementInterface $a = null,
		StructureElementInterface $b = null, $extra = null)
	{
		if (!isset($existing))
			$existing = new StructureComparison(
				StructureComparison::ALTERED, $a, $b);
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

	const PAIRING_MATCH = 'matching';

	const PAIRING_RENAMED = 'renamed';

	const PAIRING_CREATED = 'created';

	const PAIRING_DROPPED = 'dropped';
}
