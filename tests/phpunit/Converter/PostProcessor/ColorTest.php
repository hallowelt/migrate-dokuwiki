<?php

namespace HalloWelt\MigrateDokuwiki\Tests\Converter\PostProcessor;

use HalloWelt\MigrateDokuwiki\Converter\PostProcessors\Color;
use PHPUnit\Framework\TestCase;

class ColorTest extends TestCase {
	/**
	 * @covers \HalloWelt\MigrateDokuwiki\Converter\PostProcessor\Color::process()
	 */
	public function testProcess() {
		$processor = new Color();

		$dataDir = dirname( __DIR__, 2 ) . '/data/Converter/PostProcessor';
		$input = file_get_contents( "$dataDir/color-input.txt" );
		$expected = file_get_contents( "$dataDir/color-output.txt" );
		$actual = $processor->process( $input );
		$this->assertEquals( $expected, $actual );
	}
}
