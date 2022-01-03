<?php
/**
 * Copyright © 2021 - 2022 by Renaud Guillard (dev@nore.fr)
 * Distributed under the terms of the MIT License, see LICENSE
 *
 * @package SQL
 */
namespace NoreSources\SQL;

use NoreSources\SQL\Syntax\TokenStream;
use PHPUnit\Framework\TestCase;

final class TokenStreamTest extends TestCase
{

	public function testInsertAt()
	{
		$stream = new TokenStream();
		$stream->text('hello')->text('world');
		$stream->streamAt((new TokenStream())->space(), 1);

		$this->assertEquals(
			self::toJson(
				[
					[
						TokenStream::INDEX_TOKEN => 'hello',
						TokenStream::INDEX_TYPE => TokenStream::TEXT
					],
					[
						TokenStream::INDEX_TYPE => TokenStream::SPACE
					],
					[
						TokenStream::INDEX_TOKEN => 'world',
						TokenStream::INDEX_TYPE => TokenStream::TEXT
					]
				]), self::toJson($stream), 'Insert at');
	}

	public static function toJson($data)
	{
		return \json_encode($data, JSON_PRETTY_PRINT);
	}
}
