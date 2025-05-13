<?php

namespace HalloWelt\MigrateDokuwiki\Tests\Converter\PostProcessor;

use HalloWelt\MigrateDokuwiki\Converter\PostProcessors\Image;
use PHPUnit\Framework\TestCase;

class ImageTest extends TestCase {
	/**
	 * @covers \HalloWelt\MigrateDokuwiki\Converter\PostProcessor\Image::process()
	 */
	public function testProcess() {
		$config = [
			'media-link-extensions' => [ 'pdf' ]
		];
		$processor = new Image( $config );

		$dataDir = dirname( __DIR__, 2 ) . '/data/Converter/PostProcessor';
		$input = file_get_contents( "$dataDir/image-input.txt" );
		$expected = file_get_contents( "$dataDir/image-output.txt" );
		$actual = $processor->process( $input );
		$this->assertEquals( $expected, $actual );
	}
}
