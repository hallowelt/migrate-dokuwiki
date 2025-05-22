<?php

namespace HalloWelt\MigrateDokuwiki\Tests\Converter\PreProcessor;

use HalloWelt\MigrateDokuwiki\Converter\PreProcessors\RemoveLinebreakBeforeListItems;
use PHPUnit\Framework\TestCase;

class RemoveLinebreakBeforeListItemsTest extends TestCase {

	/**
	 * @covers \HalloWelt\MigrateDokuwiki\Converter\PreProcessors\RemoveLinebreakBeforeListItems::process()
	 */
	public function testProcess() {
		$processor = new RemoveLinebreakBeforeListItems();

		$dataDir = dirname( __DIR__, 2 ) . '/data/Converter/PreProcessor';
		$input = file_get_contents( "$dataDir/remove-linebreak-before-list-items-input.txt" );
		$expected = file_get_contents( "$dataDir/remove-linebreak-before-list-items-output.txt" );
		$actual = $processor->process( $input );
		$this->assertEquals( $expected, $actual );
	}
}
