<?php

namespace HalloWelt\MigrateDokuwiki\Tests\Converter\PostProcessor\Table;

use HalloWelt\MigrateDokuwiki\Converter\PostProcessors\Table\Rowspan;
use PHPUnit\Framework\TestCase;

class RowspanTest extends TestCase {

	/**
	 * @covers \HalloWelt\MigrateDokuwiki\Converter\PostProcessors\Table\Rowspan::process()
	 */
	public function testProcess() {
		$processor = new Rowspan();

		$dataDir = dirname( __DIR__, 3 ) . '/data/Converter/PostProcessor/Table';
		$input = file_get_contents( "$dataDir/rowspan-input.txt" );
		$expected = file_get_contents( "$dataDir/rowspan-output.txt" );
		$actual = $processor->process( $input );
		$this->assertEquals( $expected, $actual );
	}
}