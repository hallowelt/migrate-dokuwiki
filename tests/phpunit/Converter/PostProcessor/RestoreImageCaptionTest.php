<?php

namespace HalloWelt\MigrateDokuwiki\Tests\Converter\PostProcessor;

use HalloWelt\MigrateDokuwiki\Converter\PostProcessors\RestoreImageCaption;
use PHPUnit\Framework\TestCase;

class RestoreImageCaptionTest extends TestCase {
	/**
	 * @covers \HalloWelt\MigrateDokuwiki\Converter\PostProcessor\RestoreImageCaption::process()
	 */
	public function testProcess() {
		$processor = new RestoreImageCaption();

		$dataDir = dirname( __DIR__, 2 ) . '/data/Converter/PostProcessor';
		$input = file_get_contents( "$dataDir/restore-image-caption-input.txt" );
		$expected = file_get_contents( "$dataDir/restore-image-caption-output.txt" );
		$actual = $processor->process( $input );
		$this->assertEquals( $expected, $actual );
	}
}
