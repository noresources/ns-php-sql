<?php
namespace NoreSources\SQL;

/**
 */
abstract class StructureSerializer implements \Serializable
{

	/**
	 * Unserialize from file
	 *
	 * @param string $filename
	 */
	public function userializeFromFile($filename)
	{
		return $this->unserialize(file_get_contents($filename));
	}

	/**
	 * Serialize to file
	 *
	 * @param string $filename
	 */
	public function serializeToFile($filename)
	{
		return file_put_contents($filename, $this->serialize());
	}

	public function __construct(StructureElement $element = null)
	{
		$this->structureElement = $element;
	}

	/**
	 *
	 * @property-read StructureElement $structureElement
	 * @param string $member
	 * @throws \InvalidArgumentException
	 * @return \NoreSources\SQL\StructureElement
	 */
	public function __get($member)
	{
		if ($member == 'structureElement')
		{
			return $this->structureElement;
		}

		throw new \InvalidArgumentException($member);
	}

	/**
	 *
	 * @var StructureElement
	 */
	protected $structureElement;
}
