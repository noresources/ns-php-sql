<?php
/**
 * Copyright © 2020 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL\Syntax\Statement;

use NoreSources\Container;
use NoreSources\SingletonTrait;
use NoreSources\SQL\Constants as K;
use NoreSources\SQL\Syntax\TokenStream;
use NoreSources\SQL\Syntax\TokenStreamContextInterface;
use NoreSources\SQL\Syntax\TokenStreamExporterInterface;
use NoreSources\SQL\Syntax\TokenizableExpressionInterface;
use NoreSources\SQL\Syntax\Tokenizer;
use phpDocumentor\Reflection\Types\Expression;

/**
 * This should be used as base class for all DBMS-specific statement builders.
 */
class StatementBuilder implements TokenStreamExporterInterface
{

	use SingletonTrait;

	public function __construct()
	{}

	public function __invoke()
	{
		return \call_user_func_array([
			$this,
			'build'
		], func_get_args());
	}

	/**
	 *
	 * @param Expression $expression
	 * @param ... ...$context
	 *        	StatementTokenStreamContext constructor arguments.
	 * @return StatementData
	 */
	public function build(TokenizableExpressionInterface $expression,
		...$context)
	{
		$stream = new TokenStream();
		if (\count($context) &&
			($first = Container::firstValue($context)) instanceof StatementTokenStreamContext)
			$context = clone $first;
		else
			$context = new StatementTokenStreamContext(...$context);

		if ($expression instanceof StatementTypeProviderInterface)
			$context->setStatementType($expression->getStatementType());

		Tokenizer::getInstance()->tokenizeExpression($expression,
			$stream, $context);
		return $this->export($stream, $context);
	}

	/**
	 *
	 * @return StatementData
	 */
	public function export(TokenStream $stream,
		TokenStreamContextInterface $context)
	{
		$data = new StatementData($context);
		$sql = '';

		foreach ($stream as $token)
		{
			$type = $token[TokenStream::INDEX_TYPE];

			if ($type == K::TOKEN_COMMENT)
				continue;

			if ($type == K::TOKEN_SPACE)
			{
				$sql .= ' ';
				continue;
			}

			$value = $token[TokenStream::INDEX_TOKEN];

			if ($type == K::TOKEN_KEYWORD)
			{
				if (\is_integer($value))
					$value = $context->getPlatform()->getKeyword($value);
			}
			elseif ($type == K::TOKEN_PARAMETER)
			{
				$name = \strval($value);
				$dbmsName = $context->getPlatform()->getParameter($name,
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
