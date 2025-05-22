<?php

namespace HalloWelt\MigrateDokuwiki\Tests\Converter\PreProcessor;

use HalloWelt\MigrateDokuwiki\Converter\PreProcessors\PreserveIndexMenu;
use PHPUnit\Framework\TestCase;

class PreserveIndexMenuTest extends TestCase {

	/**
	 * @covers \HalloWelt\MigrateDokuwiki\Converter\PreProcessors\PreserveIndexMenu::process()
	 */
	public function testProcess() {
		$processor = new PreserveIndexMenu();

		$dataDir = dirname( __DIR__, 2 ) . '/data/Converter/PreProcessor';
		$input = file_get_contents( "$dataDir/preserve-indexmenu-input.txt" );
		$expected = file_get_contents( "$dataDir/preserve-indexmenu-output.txt" );
		$actual = $processor->process( $input );
		$this->assertEquals( $expected, $actual );
	}
}
