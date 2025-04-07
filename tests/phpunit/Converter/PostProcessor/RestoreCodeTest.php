<?php

namespace HalloWelt\MigrateDokuwiki\Tests\Converter\PostProcessor;

use HalloWelt\MigrateDokuwiki\Converter\PostProcessors\RestoreCode;
use PHPUnit\Framework\TestCase;

class RestoreCodeTest extends TestCase {
	/**
	 * @covers \HalloWelt\MigrateDokuwiki\Converter\PostProcessor\RestoreCode::process()
	 */
	public function testProcess() {
		$processor = new RestoreCode();

		$dataDir = dirname( __DIR__, 2 ) . '/data/Converter/PostProcessor';
		$input = file_get_contents( "$dataDir/restore-code-input.txt" );
		$expected = file_get_contents( "$dataDir/restore-code-output.txt" );
		$actual = $processor->process( $input );
		$this->assertEquals( $expected, $actual );
	}
}
