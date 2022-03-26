<?php

/**
 * Copyright © 2021 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\DBMS\Manager;

use NoreSources\ComparableInterface;
use NoreSources\Container\ChainElementInterface;
use NoreSources\Container\ChainElementTrait;
use NoreSources\Container\Container;
use NoreSources\SQL\Structure\StructureElementInterface;
use NoreSources\SQL\Structure\Comparer\StructureComparison;
use NoreSources\SQL\Structure\Inspector\StructureInspector;
use NoreSources\Type\TypeDescription;

class StructureOperation implements ComparableInterface,
	ChainElementInterface
{

	use ChainElementTrait;

	const ALTER = 'alter';

	const BACKUP = 'backup';

	const CREATE = 'create';

	const DROP = 'drop';

	const RENAME = 'rename';

	const RESTORE = 'restore';

	public function __toString()
	{
		$s = $this->getType() . ':' .
			TypeDescription::getLocalName($this->getStructure());

		if (isset($this->reference))
		{
			$s .= ':' . $this->reference->getName();
			if (isset($this->target))
			{
				$s .= '->' . $this->target->getName();
			}
		}
		elseif (isset($this->target))
			$s .= ':->' . $this->target->getName();
		return $s;
	}

	/**
	 *
	 * @param StructureOperation $value
	 */
	public function compare($b)
	{

		/** @var StructureOperation $b */
		$a = $this;
		$ta = $a->getType();
		$tb = $b->getType();
		$stra = \strval($a);
		$strb = \strval($b);

		// Backup is always the first operation

		if ($ta == self::BACKUP)
		{
			if ($tb == self::BACKUP)
				return \strcmp($stra, $strb);
			return -1;
		}

		if ($tb == self::BACKUP)
			return 1;

		$ra = $a->getOriginalOperation();
		$rb = $b->getOriginalOperation();
		$sra = $a->getReference();
		$srb = $b->getReference();
		$sta = $a->getTarget();
		$stb = $b->getTarget();

		$sra_dependsOn_srb = null;
		$sta_dependsOn_stb = null;
		$srb_dependsOn_sra = null;
		$stb_dependsOn_sta = null;

		if ($ta == self::ALTER)
		{}
		elseif ($ta == self::CREATE)
		{
			switch ($tb)
			{
				case self::ALTER:
				break;
				case self::CREATE:
					if (self::dependsOn($sta_dependsOn_stb, $sta, $stb))
						return 1;
					if (self::dependsOn($stb_dependsOn_sta, $stb, $sta))
						return -1;
				break;
				case self::DROP:
					if (StructureInspector::getInstance()->conflictsWith(
						$sta, $srb))
						return 1;
					if (!$rb || ($rb->getType() == self::BACKUP))
						return 1;
					if (!StructureInspector::getInstance()->hasData(
						$srb))
						return 1;
				break;
				case self::RESTORE:
					if (!StructureInspector::getInstance()->hasData(
						$sta))
						return 1;
					if (self::dependsOn($sta_dependsOn_stb, $sta, $stb))
						return 1;
					if (self::dependsOn($stb_dependsOn_sta, $stb, $sta))
						return -1;
				break;

				case self::RENAME:
				case self::RESTORE:
					if (self::dependsOn($stb_dependsOn_sta, $stb, $sta))
						return -1;
				break;
			}
		}
		elseif ($ta == self::DROP)
		{
			switch ($tb)
			{
				case self::ALTER:
				break;
				case self::CREATE:
					var_dump(
						[
							$ta => TypeDescription::getLocalName($sra),
							$tb => TypeDescription::getLocalName($stb)
						]);
					if (StructureInspector::getInstance()->conflictsWith(
						$sra, $stb))
						return -1;
				break;
				case self::DROP:
					if (!StructureInspector::getInstance()->hasData(
						$sra))
					{
						if (StructureInspector::getInstance()->hasData(
							$srb))
							return -1;
						return strcmp($stra, $strb);
					}
					elseif (!StructureInspector::getInstance()->hasData(
						$srb))
						return 1;

					if (self::dependsOn($sra_dependsOn_srb, $sra, $srb))
						return -1;
					if (self::dependsOn($srb_dependsOn_sra, $srb, $sra))
						return 1;

					if ($ra)
					{
						if (!$rb)
							return 1;
					}
					elseif ($rb)
						return -1;
				break;
				case self::RENAME:
				break;
				case self::RESTORE:
					if (StructureInspector::getInstance()->conflictsWith(
						$stb, $sra))
						return -1;
					$backup = $b->getOriginalOperation();
					if (StructureInspector::getInstance()->dependencyCompare(
						$sra, $backup->getReference()))
						return -1;
				break;
			}
		}
		elseif ($ta == self::RENAME)
		{

			switch ($tb)
			{
				case self::CREATE:
					if (self::dependsOn($stb_dependsOn_sta, $stb, $sta))
						return -1;

				case self::DROP:
				break;
			}
		}
		elseif ($ta == self::RESTORE)
		{
			switch ($tb)
			{
				case self::ALTER:
				break;
				case self::CREATE:
					if (self::dependsOn($stb_dependsOn_sta, $stb, $sta))
						return -1;
					if (!StructureInspector::getInstance()->hasData(
						$stb))
						return -1;
				break;
				case self::DROP:
					if (StructureInspector::getInstance()->conflictsWith(
						$sta, $srb))
						return 1;
					if ($rb && $rb->getType() == self::BACKUP)
						return 1;
					if (!StructureInspector::getInstance()->hasData(
						$srb))
						return 1;
					$backup = $a->getOriginalOperation();

					if (StructureInspector::getInstance()->dependencyCompare(
						$srb, $backup->getReference()))
						return 1;
				break;
				case self::RENAME:
				break;
				case self::RESTORE:
					if (self::dependsOn($sta_dependsOn_stb, $sta, $stb))
						return 1;
					if (self::dependsOn($stb_dependsOn_sta, $stb, $sta))
						return -1;
				break;
			}
		}

		return strcmp($stra, $strb);
	}

	public function getType()
	{
		return $this->operationType;
	}

	/**
	 *
	 * @return StructureElementInterface
	 */
	public function getReference()
	{
		return $this->reference;
	}

	/**
	 *
	 * @return StructureElementInterface
	 */
	public function getTarget()
	{
		return $this->target;
	}

	/**
	 *
	 * @return StructureElementInterface
	 */
	public function getStructure()
	{
		if (isset($this->reference))
			return $this->reference;
		return $this->target;
	}

	/**
	 *
	 * @return StructureOperation|NULL
	 */
	public function getOriginalOperation()
	{
		$r = null;
		$p = $this->getPreviousElement();
		while ($p)
		{
			$r = $p;
			$p = $p->getPreviousElement();
		}
		return $r;
	}

	public static function createFromDifference(
		StructureComparison $difference)
	{
		$type = Container::keyValue(
			[
				StructureComparison::ALTERED => self::ALTER,
				StructureComparison::CREATED => self::CREATE,
				StructureComparison::DROPPED => self::DROP,
				StructureComparison::RENAMED => self::RENAME
			], $difference->getType());
		return new StructureOperation($type, $difference->getReference(),
			$difference->getTarget());
	}

	public function __construct($type,
		StructureElementInterface $reference = null,
		StructureElementInterface $target = null)
	{
		$this->operationType = $type;
		$this->reference = $reference;
		$this->target = $target;

		// DEBUG
		if ($this->operationType == self::CREATE && !$this->target)
			throw new \Exception();
	}

	private static function dependsOn(&$var,
		StructureElementInterface $a, StructureElementInterface $b)
	{
		if (\is_bool($var))
			return $var;
		$var = StructureInspector::getInstance()->dependsOn($a, $b);
		return $var;
	}

	/**
	 *
	 * @var string
	 */
	private $operationType;

	/**
	 *
	 * @var StructureElementInterface
	 */
	private $reference;

	/**
	 *
	 * @var StructureElementInterface
	 */
	private $target;
}
