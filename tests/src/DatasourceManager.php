<?php
namespace NoreSources\Test;

use PHPUnit\Framework\TestCase;

class DatasourceManager extends TestCase
{

	use DatasourceManagerTrait;

	public function __construct($name = null, array $data = [],
		$dataName = '', $basePath = null)
	{
		parent::__construct($name ? $name : static::class, $data,
			$dataName);
		$this->initializeDatasourceManager($basePath);
	}

	public function get($name, $reload = false)
	{
		return $this->getDatasource($name, $reload);
	}
}
