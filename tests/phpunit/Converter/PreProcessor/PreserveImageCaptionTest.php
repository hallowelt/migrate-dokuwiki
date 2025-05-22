<?php

namespace HalloWelt\MigrateDokuwiki\Tests\Converter\PreProcessor;

use HalloWelt\MigrateDokuwiki\Converter\PreProcessors\PreserveImageCaption;
use PHPUnit\Framework\TestCase;

class PreserveImageCaptionTest extends TestCase {

	/**
	 * @covers \HalloWelt\MigrateDokuwiki\Converter\PreProcessors\PreserveImageCaption::process()
	 */
	public function testProcess() {
		$processor = new PreserveImageCaption();

		$dataDir = dirname( __DIR__, 2 ) . '/data/Converter/PreProcessor';
		$input = file_get_contents( "$dataDir/preserve-image-caption-input.txt" );
		$expected = file_get_contents( "$dataDir/preserve-image-caption-output.txt" );
		$actual = $processor->process( $input );
		$this->assertEquals( $expected, $actual );
	}
}
