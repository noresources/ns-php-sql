<?php
namespace NoreSources\SQL\Expression;

use NoreSources\SQL as sql;
use NoreSources\Expression as xpr;

class Column extends StructureElementIdentifier
{

	use xpr\BasicExpressionVisitTrait;

	public function __construct($path)
	{
		parent::__construct($path);
	}

	public function tokenize(sql\TokenStream &$stream, sql\BuildContext $context)
	{
		$target = $context->findColumn($this->path);
		if ($target instanceof sql\TableColumnStructure)
		{
			$parts = explode('.', $this->path);
			foreach ($parts as $part)
			{
				if ($context->isAlias($part))
					return $stream->identifier($context->escapeIdentifierPath($parts));
			}

			return $stream->identifier($context->getCanonicalName($target));
		}
		else
			return $stream->identifier($context->escapeIdentifier($this->path));
	}
}