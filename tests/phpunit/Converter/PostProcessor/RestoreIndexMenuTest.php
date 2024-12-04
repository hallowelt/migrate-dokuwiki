<?php

namespace HalloWelt\MigrateDokuwiki\Tests\Converter\PostProcessor;

use HalloWelt\MigrateDokuwiki\Converter\PostProcessors\RestoreIndexMenu;
use PHPUnit\Framework\TestCase;

class RestoreIndexMenuTest extends TestCase {
	/**
	 * @covers \HalloWelt\MigrateDokuwiki\Converter\PostProcessor\RestoreIndexMenu::process()
	 */
	public function testProcess() {
		$pageKeyToTitleMap = [
			'test:page' => 'Test:Page'
		];

		$processor = new RestoreIndexMenu( $pageKeyToTitleMap );

		$dataDir = dirname( __DIR__, 2 ) . '/data/Converter/PostProcessor';
		$input = file_get_contents( "$dataDir/restore-indexmenu-input.txt" );
		$expected = file_get_contents( "$dataDir/restore-indexmenu-output.txt" );
		$actual = $processor->process( $input );
		$this->assertEquals( $expected, $actual );
	}
}
