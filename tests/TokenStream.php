<?php
namespace NoreSources\SQL;

use NoreSources\SQL\Syntax\TokenStream;

final class TokenStremTest extends \PHPUnit\Framework\TestCase
{

	public function testInsertAt()
	{
		$stream = new TokenStream();
		$stream->text('hello')->text('world');
		$stream->insertAt(' ', TokenStream::SPACE, 1);

		$this->assertEquals(
			self::toJson(
				[
					[
						TokenStream::INDEX_TOKEN => 'hello',
						TokenStream::INDEX_TYPE => TokenStream::TEXT
					],
					[
						TokenStream::INDEX_TOKEN => ' ',
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
