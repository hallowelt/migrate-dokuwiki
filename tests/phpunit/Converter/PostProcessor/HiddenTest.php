<?php

namespace HalloWelt\MigrateDokuwiki\Tests\Converter\PostProcessor;

use HalloWelt\MigrateDokuwiki\Converter\PostProcessors\Hidden;
use PHPUnit\Framework\TestCase;

class HiddenTest extends TestCase {
	/**
	 * @covers \HalloWelt\MigrateDokuwiki\Converter\PostProcessor\Hidden::process()
	 */
	public function testProcess() {
		$processor = new Hidden();

		$dataDir = dirname( __DIR__, 2 ) . '/data/Converter/PostProcessor';
		$input = file_get_contents( "$dataDir/hidden-input.txt" );
		$expected = file_get_contents( "$dataDir/hidden-output.txt" );
		$actual = $processor->process( $input );
		$this->assertEquals( $expected, $actual );
	}
}
