<?php

namespace HalloWelt\MigrateDokuwiki\Tests\Converter\PostProcessor;

use HalloWelt\MigrateDokuwiki\Converter\PostProcessors\RestoreInclude;
use PHPUnit\Framework\TestCase;

class RestoreIncludeTest extends TestCase {
	/**
	 * @covers \HalloWelt\MigrateDokuwiki\Converter\PostProcessor\RestoreInclude::process()
	 */
	public function testProcess() {
		$processor = new RestoreInclude();

		$dataDir = dirname( __DIR__, 2 ) . '/data/Converter/PostProcessor';
		$input = file_get_contents( "$dataDir/restore-include-input.txt" );
		$expected = file_get_contents( "$dataDir/restore-include-output.txt" );
		$actual = $processor->process( $input );
		$this->assertEquals( $expected, $actual );
	}
}
