<?php

namespace HalloWelt\MigrateDokuwiki\Tests\Converter\PreProcessor\Table;

use HalloWelt\MigrateDokuwiki\Converter\PreProcessors\Table\RemoveLinebreakBeforeTable;
use PHPUnit\Framework\TestCase;

class RemoveLinebreakBeforeTableTest extends TestCase {
	/**
	 * @covers \HalloWelt\MigrateDokuwiki\Converter\PreProcessors\Table\RemoveLinebreakBeforeTable::process()
	 */
	public function testProcess() {
		$processor = new RemoveLinebreakBeforeTable();

		$dataDir = dirname( __DIR__, 3 ) . '/data/Converter/PreProcessor/Table';
		$input = file_get_contents( "$dataDir/remove-linebreak-before-table-input.txt" );
		$expected = file_get_contents( "$dataDir/remove-linebreak-before-table-output.txt" );
		$actual = $processor->process( $input );
		$this->assertEquals( $expected, $actual );
	}
}
