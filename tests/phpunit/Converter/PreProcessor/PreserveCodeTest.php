<?php

namespace HalloWelt\MigrateDokuwiki\Tests\Converter\PreProcessor\Table;

use HalloWelt\MigrateDokuwiki\Converter\PreProcessors\PreserveCode;
use PHPUnit\Framework\TestCase;

class PreserveCodeTest extends TestCase {

	/**
	 * @covers \HalloWelt\MigrateDokuwiki\Converter\PreProcessors\PreserveCode::process()
	 */
	public function testProcess() {
		$processor = new PreserveCode();

		$dataDir = dirname( __DIR__, 2 ) . '/data/Converter/PreProcessor';
		$input = file_get_contents( "$dataDir/preserve-code-input.txt" );
		$expected = file_get_contents( "$dataDir/preserve-code-output.txt" );
		$actual = $processor->process( $input );
		$this->assertEquals( $expected, $actual );
	}
}
