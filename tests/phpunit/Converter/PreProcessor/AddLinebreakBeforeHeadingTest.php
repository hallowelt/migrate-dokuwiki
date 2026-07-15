<?php

namespace HalloWelt\MigrateDokuwiki\Tests\Converter\PostProcessor;

use HalloWelt\MigrateDokuwiki\Converter\PreProcessors\AddLinebreakBevoreHeading;
use PHPUnit\Framework\TestCase;

class AddLinebreakBeforeHeadingTest extends TestCase {

	/**
	 * @covers \HalloWelt\MigrateDokuwiki\Converter\PostProcessors\AddLinebreakBevoreHeading::process()
	 */
	public function testProcess() {
		$processor = new AddLinebreakBevoreHeading();

		$dataDir = dirname( __DIR__, 2 ) . '/data/Converter/PreProcessor';
		$input = file_get_contents( "$dataDir/add-linebreak-before-heading-input.txt" );
		$expected = file_get_contents( "$dataDir/add-linebreak-before-heading-output.txt" );
		$actual = $processor->process( $input );
		$this->assertEquals( $expected, $actual );
	}
}
