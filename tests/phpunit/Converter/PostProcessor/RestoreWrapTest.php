<?php

namespace HalloWelt\MigrateDokuwiki\Tests\Converter\PostProcessor;

use HalloWelt\MigrateDokuwiki\Converter\PostProcessors\RestoreWrap;
use PHPUnit\Framework\TestCase;

class RestoreWrapTest extends TestCase {
	/**
	 * @covers \HalloWelt\MigrateDokuwiki\Converter\PostProcessor\RestoreWrap::process()
	 */
	public function testProcess() {
		$processor = new RestoreWrap();

		$dataDir = dirname( __DIR__, 2 ) . '/data/Converter/PostProcessor';
		$input = file_get_contents( "$dataDir/restore-wrap-input.txt" );
		$expected = file_get_contents( "$dataDir/restore-wrap-output.txt" );
		$actual = $processor->process( $input );
		$this->assertEquals( $expected, $actual );
	}
}
