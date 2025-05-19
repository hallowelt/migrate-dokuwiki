<?php

namespace HalloWelt\MigrateDokuwiki\Tests\Converter\PostProcessor;

use HalloWelt\MigrateDokuwiki\Converter\PostProcessors\Image;
use PHPUnit\Framework\TestCase;

class ImageTest extends TestCase {
	/**
	 * @covers \HalloWelt\MigrateDokuwiki\Converter\PostProcessor\Image::process()
	 */
	public function testProcess() {
		$dataDir = dirname( __DIR__, 2 ) . '/data/Converter/PostProcessor';

		$config = [
			'media-link-extensions' => [ 'pdf' ]
		];
		$processor = new Image( $config );

		$input = file_get_contents( "$dataDir/image-input.txt" );
		$expected = file_get_contents( "$dataDir/image-output.txt" );
		$actual = $processor->process( $input );
		$this->assertEquals( $expected, $actual );

		$config = [
			'media-link-extensions' => [ 'pdf' ],
			'ext-ns-file-repo-compat' => true
		];
		$processor = new Image( $config );

		$input = file_get_contents( "$dataDir/image-ns-file-repo-input.txt" );
		$expected = file_get_contents( "$dataDir/image-ns-file-repo-output.txt" );
		$actual = $processor->process( $input );
		$this->assertEquals( $expected, $actual );
	}
}
