<?php

namespace HalloWelt\MigrateDokuwiki\Tests\Converter\PreProcessor\Table;

use HalloWelt\MigrateDokuwiki\Converter\PreProcessors\Table\RemoveLinebreakAtEndOfRow;
use PHPUnit\Framework\TestCase;

class RemoveLinebreakAtEndOfRowTest extends TestCase {
	/**
	 * @covers \HalloWelt\MigrateDokuwiki\Converter\PreProcessors\Table\RemoveLinebreakAtEndOfRow::process()
	 */
	public function testProcess() {
		$processor = new RemoveLinebreakAtEndOfRow();

		$dataDir = dirname( __DIR__, 3 ) . '/data/Converter/PreProcessor/Table';
		$input = file_get_contents( "$dataDir/remove-linebreak-input.txt" );
		$expected = file_get_contents( "$dataDir/remove-linebreak-output.txt" );
		$actual = $processor->process( $input );
		$this->assertEquals( $expected, $actual );
	}
}