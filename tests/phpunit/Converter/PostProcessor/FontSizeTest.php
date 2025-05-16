<?php

namespace HalloWelt\MigrateDokuwiki\Tests\Converter\PostProcessor;

use HalloWelt\MigrateDokuwiki\Converter\PostProcessors\FontSize;
use PHPUnit\Framework\TestCase;

class FontSizeTest extends TestCase {
	/**
	 * @covers \HalloWelt\MigrateDokuwiki\Converter\PostProcessor\FontSize()::process()
	 */
	public function testProcess() {
		$processor = new FontSize();

		$dataDir = dirname( __DIR__, 2 ) . '/data/Converter/PostProcessor';
		$input = file_get_contents( "$dataDir/font-size-input.txt" );
		$expected = file_get_contents( "$dataDir/font-size-output.txt" );
		$actual = $processor->process( $input );
		$this->assertEquals( $expected, $actual );
	}
}
