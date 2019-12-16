<?php
namespace NoreSources\SQL\Expression;

use NoreSources\SQL;
use NoreSources\SQL\Statement\BuildContext;

class Column extends StructureElementIdentifier
{

	public function __construct($path)
	{
		parent::__construct($path);
	}

	public function tokenize(TokenStream &$stream, BuildContext $context)
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