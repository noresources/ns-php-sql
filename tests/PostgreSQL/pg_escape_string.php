<?php

// Just a test
$texts = [
	'Basic test',
	'A text with "double quoted" string',
	'Another text with \'single quoted\' string',
	'Single \'quotes\', "double" @nd \\other$ oÐd çharacters'
];

$i = 0;
do
{
	if ($i > 0)
		pg_connect('');
	foreach ($texts as $text)
	{
		var_dump(
			[
				$i . ' input' => $text,
				$i . ' escape_string' => pg_escape_string($text),
				$i . ' escape_bytea' => pg_escape_bytea($text),
				$i . ' escape_identifier' => @pg_escape_identifier($text),
				$i . ' escape_literal' => @pg_escape_literal($text)
				//$i . ' PDO::quote' => \PDO::quote($text)
			]);
	}
	$i++;
}
while ($i < 2);