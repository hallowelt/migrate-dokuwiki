<?php

namespace HalloWelt\MigrateDokuwiki\Tests\Converter\PreProcessor\Table;

use HalloWelt\MigrateDokuwiki\Converter\PreProcessors\EnsureListIndention;
use PHPUnit\Framework\TestCase;

class EnsureListIndentionTest extends TestCase {

	/**
	 * @covers \HalloWelt\MigrateDokuwiki\Converter\PreProcessors\PreserveWrap::process()
	 */
	public function testProcess() {
		$processor = new EnsureListIndention();

		$dataDir = dirname( __DIR__, 2 ) . '/data/Converter/PreProcessor';
		$input = file_get_contents( "$dataDir/ensure-list-item-indention-input.txt" );
		$expected = file_get_contents( "$dataDir/ensure-list-item-indention-output.txt" );
		$actual = $processor->process( $input );
		$this->assertEquals( $expected, $actual );
	}
}
