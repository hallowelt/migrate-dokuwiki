<?php

namespace HalloWelt\MigrateDokuwiki\Tests\Converter\PreProcessor\Table;

use HalloWelt\MigrateDokuwiki\Converter\PreProcessors\Table\PreserveTableWidth;
use PHPUnit\Framework\TestCase;

class PreserveTableWidthTest extends TestCase {

	/**
	 * @covers \HalloWelt\MigrateDokuwiki\Converter\PreProcessors\Table\PreserveTableWidth::process()
	 */
	public function testProcess() {
		$processor = new PreserveTableWidth();

		$dataDir = dirname( __DIR__, 3 ) . '/data/Converter/PreProcessor/Table';
		$input = file_get_contents( "$dataDir/preserve-table-width-input.txt" );
		$expected = file_get_contents( "$dataDir/preserve-table-width-output.txt" );
		$actual = $processor->process( $input );
		$this->assertEquals( $expected, $actual );
	}
}
