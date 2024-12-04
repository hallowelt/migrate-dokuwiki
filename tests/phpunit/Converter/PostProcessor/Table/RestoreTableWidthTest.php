<?php

namespace HalloWelt\MigrateDokuwiki\Tests\Converter\PostProcessor\Table;

use HalloWelt\MigrateDokuwiki\Converter\PostProcessors\Table\RestoreTableWidth;
use PHPUnit\Framework\TestCase;

class RestoreTableWidthTest extends TestCase {

	/**
	 * @covers \HalloWelt\MigrateDokuwiki\Converter\PostProcessors\Table\RestoreTableWidth::process()
	 */
	public function testProcess() {
		$processor = new RestoreTableWidth();

		$dataDir = dirname( __DIR__, 3 ) . '/data/Converter/PostProcessor/Table';
		$input = file_get_contents( "$dataDir/restore-table-width-input.txt" );
		$expected = file_get_contents( "$dataDir/restore-table-width-output.txt" );
		$actual = $processor->process( $input );
		$this->assertEquals( $expected, $actual );
    }
}