<?php

namespace HalloWelt\MigrateDokuwiki\Tests\Converter\PostProcessor;

use HalloWelt\MigrateDokuwiki\Converter\PreProcessors\ConvertArrowInHeading;
use PHPUnit\Framework\TestCase;

class ConvertArrowInHeadingTest extends TestCase {

	/**
	 * @covers \HalloWelt\MigrateDokuwiki\Converter\PostProcessors\ConvertArrowInHeading::process()
	 */
	public function testProcess() {
		$processor = new ConvertArrowInHeading();

		$dataDir = dirname( __DIR__, 2 ) . '/data/Converter/PreProcessor';
		$input = file_get_contents( "$dataDir/convert-arrow-in-heading-input.txt" );
		$expected = file_get_contents( "$dataDir/convert-arrow-in-heading-output.txt" );
		$actual = $processor->process( $input );
		$this->assertEquals( $expected, $actual );
	}
}
