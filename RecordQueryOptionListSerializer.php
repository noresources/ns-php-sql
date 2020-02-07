<?php
namespace NoreSources\SQL;

use NoreSources\ArrayUtil;

class RecordQueryOptionListSerializer implements \Serializable, \JsonSerializable
{

	public function jsonSerialize()
	{
		return $this->serialize();
	}

	public function serialize($options)
	{
		$data = [];
		foreach ($options as $option)
		{
			if ($option instanceof ColumnValueFilter)
			{}
		}
	}

	public function unserialize($content)
	{
		if (\is_string($content))
			$content = @json_decode($content, true);
		if (!ArrayUtil::isArray($content))
			return [];

		$filters = [];

		foreach ($content as $key => $filterSpec)
		{
			if (\is_string($key))
			{
				if (ArrayUtil::isArray($filterSpec))
				{
					$cls = new \ReflectionClass(ColumnValueFilter::class);
					$filters[] = $cls->newInstanceArgs($filterSpec);
				}
				else
				{
					$filters[] = new ColumnValueFilter($key, '=', $filterSpec);
				}
				continue;
			}

			$classKey = ArrayUtil::keyValue($filterSpec, 'type', ColumnValueFilter::class);
			$arguments = ArrayUtil::keyValue($filterSpec, 'arguments', []);
			$cls = self::getFilterC$classKey);
			$filters[] = $cls->newInstanceArgs($arguments);
		}
		return $filters;
	}

	public static function getFilterClass($key)
	{
		if (\class_exists($key) && \is_subclass_of($key, RecordQueryOption::class))
			return new \ReflectionClass($key);
		if (!\is_array(self::$classKeyMap))
			self::$classKeyMap = [
				'presentation' => PresentationSettings::class,
				'value' => ColumnValueFilter::class,
				'columns' => ColumnSelectionFilter::class,
				'order' => OrderingOption::class,
				'group' => GroupingOption::class,
				'limit' => LimitFilter::class
			];

		if (!ArrayUtil::keyExists(self::$classKeyMap, $key)) throw new \InvalidArgumentException($key);
	}
	
	private static $classKeyMap;
}











