<?php
use NoreSources\Parser;
use NoreSources\ProgramInfo;
use NoreSources\ProgramResult;
use NoreSources\UsageFormat;

require_once (__DIR__ . '/Parser.php');

abstract class AbstractToolApplication
{

	/**
	 *
	 * @var ProgramInfo
	 */
	public $programInfo;

	protected function __construct(ProgramInfo $programInfo)
	{
		$this->programInfo = $programInfo;
	}

	public static function main($argv)
	{
		$cls = new ReflectionClass(static::class);
		$app = $cls->newInstance();

		$parser = new Parser($app->programInfo);
		$result = $parser->parse($argv);
		$usage = new UsageFormat();

		if ($result->displayHelp->isSet)
		{
			echo ($app->programInfo->usage($usage));
			return 0;
		}

		if (!$result())
		{
			foreach ($result->getMessages() as $m)
			{
				echo (" - " . $m . "\n");
			}
			echo ($app->programInfo->usage($usage));
			return 1;
		}

		return $app->run($result);
	}

	abstract function run(ProgramResult $result);
}

