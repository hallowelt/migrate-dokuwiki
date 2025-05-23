<?php

namespace HalloWelt\MigrateDokuwiki\Tests\Converter\PostProcessor\Table;

use HalloWelt\MigrateDokuwiki\Converter\PostProcessors\Table\Colspan;
use PHPUnit\Framework\TestCase;

class ColspanTest extends TestCase {

	/**
	 * @covers \HalloWelt\MigrateDokuwiki\Converter\PostProcessors\Table\Colspan::process()
	 */
	public function testProcess() {
		$processor = new Colspan();

		$dataDir = dirname( __DIR__, 3 ) . '/data/Converter/PostProcessor/Table';
		$input = file_get_contents( "$dataDir/colspan-input.txt" );
		$expected = file_get_contents( "$dataDir/colspan-output.txt" );
		$actual = $processor->process( $input );
		$this->assertEquals( $expected, $actual );
	}
}
