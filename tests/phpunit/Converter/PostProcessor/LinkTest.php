<?php

namespace HalloWelt\MigrateDokuwiki\Tests\Converter\PostProcessor;

use HalloWelt\MigrateDokuwiki\Converter\PostProcessors\Link;
use PHPUnit\Framework\TestCase;

class LinkTest extends TestCase {
	/**
	 * @covers \HalloWelt\MigrateDokuwiki\Converter\PostProcessor\Link::process()
	 */
	public function testProcess() {
		$processor = new Link();

		$dataDir = dirname( __DIR__, 2 ) . '/data/Converter/PostProcessor';
		$input = file_get_contents( "$dataDir/link-input.txt" );
		$expected = file_get_contents( "$dataDir/link-output.txt" );
		$actual = $processor->process( $input );
		$this->assertEquals( $expected, $actual );
	}
}
