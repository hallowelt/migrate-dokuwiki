<?php

namespace HalloWelt\MigrateDokuwiki\Tests\Converter\PreProcessor\Table;

use HalloWelt\MigrateDokuwiki\Converter\PreProcessors\PreserveWrap;
use PHPUnit\Framework\TestCase;

class PreserveWrapTest extends TestCase {

	/**
	 * @covers \HalloWelt\MigrateDokuwiki\Converter\PreProcessors\PreserveWrap::process()
	 */
	public function testProcess() {
		$processor = new PreserveWrap();

		$dataDir = dirname( __DIR__, 2 ) . '/data/Converter/PreProcessor';
		$input = file_get_contents( "$dataDir/preserve-wrap-input.txt" );
		$expected = file_get_contents( "$dataDir/preserve-wrap-output.txt" );
		$actual = $processor->process( $input );
		$this->assertEquals( $expected, $actual );
	}
}