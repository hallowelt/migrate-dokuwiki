<?php

namespace HalloWelt\MigrateDokuwiki\Tests\Converter\PostProcessor;

use HalloWelt\MigrateDokuwiki\Converter\PostProcessors\ListItems;
use PHPUnit\Framework\TestCase;

class ListItemsTest extends TestCase {

	/**
	 * @covers \HalloWelt\MigrateDokuwiki\Converter\PostProcessor\ListItems::process()
	 */
	public function testProcess() {
		$processor = new ListItems();

		$dataDir = dirname( __DIR__, 2 ) . '/data/Converter/PostProcessor';
		$input = file_get_contents( "$dataDir/listitems-input.txt" );
		$expected = file_get_contents( "$dataDir/listitems-output.txt" );
		$actual = $processor->process( $input );
		$this->assertEquals( $expected, $actual );
	}
}
