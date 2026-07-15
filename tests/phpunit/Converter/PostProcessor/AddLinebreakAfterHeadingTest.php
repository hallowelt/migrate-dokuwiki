<?php

namespace HalloWelt\MigrateDokuwiki\Tests\Converter\PostProcessor;

use HalloWelt\MigrateDokuwiki\Converter\PostProcessors\AddLinebreakAfterHeading;
use PHPUnit\Framework\TestCase;

class AddLinebreakAfterHeadingTest extends TestCase {

	/**
	 * @covers \HalloWelt\MigrateDokuwiki\Converter\PostProcessors\AddLinebreakAfterHeading::process()
	 */
	public function testProcess() {
		$processor = new AddLinebreakAfterHeading();

		$dataDir = dirname( __DIR__, 2 ) . '/data/Converter/PostProcessor';
		$input = file_get_contents( "$dataDir/add-linebreak-after-heading-input.txt" );
		$expected = file_get_contents( "$dataDir/add-linebreak-after-heading-output.txt" );
		$actual = $processor->process( $input );
		$this->assertEquals( $expected, $actual );
	}
}
