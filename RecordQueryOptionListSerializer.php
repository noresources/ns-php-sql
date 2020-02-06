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

		foreach ($content as $key => $arguments)
		{
			$clsName = null;
			if ($key == 'value')
			{
				$cls = new \ReflectionClass(ColumnValueFilter::class);
				$filters[] = $cls->newInstanceArgs($arguments);
			}
			elseif ($key == 'limit')
			{
				$cls = new \ReflectionClass(LimitFilter::class);
				$filters[] = $cls->newInstanceArgs($arguments);
			}
			elseif ($key == 'group')
			{
				$cls = new \ReflectionClass(GroupingOption::class);
				$filters[] = $cls->newInstanceArgs($arguments);
			}
			elseif ($key == 'order')
			{
				$cls = new \ReflectionClass(OrderingOption::class);
				$filters[] = $cls->newInstanceArgs($arguments);
			}
			elseif ($key == 'columns')
			{
				$filters[] = new ColumnSelectionFilter($arguments);
			}
			elseif ($key == 'presentation')
			{
				$filters[] = new PresentationSettings($arguments);
			}
			else
			{
				$filters[] = new ColumnValueFilter($key, '=', $arguments);
			}
		}

		return $filters;
	}
}











