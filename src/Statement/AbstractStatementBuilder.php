<?php
/**
 * Copyright © 2012 - 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 */
/**
 *
 * @package SQL
 */
namespace NoreSources\SQL\Statement;

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Expression\TokenStream;
use NoreSources\SQL\Expression\TokenStreamContextInterface;

/**
 * Generic, partial implementation of StatementBuilderInterface.
 *
 * This should be used as base class for all DBMS-specific statement builders.
 */
abstract class AbstractStatementBuilder implements
	StatementBuilderInterface
{

	/**
	 *
	 * @param number $flags
	 *        	AbstractStatementBuilder flags
	 */
	public function __construct()
	{}

	public function finalizeStatement(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$data = new StatementData($context);
		$sql = '';

		foreach ($stream as $token)
		{
			$value = $token[TokenStream::INDEX_TOKEN];
			$type = $token[TokenStream::INDEX_TYPE];

			if ($type == K::TOKEN_PARAMETER)
			{
				$name = \strval($value);
				$dbmsName = $this->getPlatform()->getParameter($name,
					$data->getParameters());
				$position = $data->getParameters()->appendParameter(
					$name, $dbmsName);
				$value = $dbmsName;
			}

			$sql .= $value;
		}

		$data->setSQL($sql);
		return $data;
	}
}
