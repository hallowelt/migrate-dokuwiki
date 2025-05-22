<?php

namespace HalloWelt\MigrateDokuwiki\Tests\Converter\PreProcessor;

use HalloWelt\MigrateDokuwiki\Converter\PreProcessors\EmoticonsAndSymbols;
use PHPUnit\Framework\TestCase;

class EmoticonsAndSymbolsTest extends TestCase {

	/**
	 * @covers \HalloWelt\MigrateDokuwiki\Converter\PreProcessors\EmoticonsAndSymbols::process()
	 */
	public function testProcess() {
		$processor = new EmoticonsAndSymbols( [] );

		$dataDir = dirname( __DIR__, 2 ) . '/data/Converter/PreProcessor';
		$input = file_get_contents( "$dataDir/emoticons-and-symbols-input.txt" );
		$expected = file_get_contents( "$dataDir/emoticons-and-symbols-output.txt" );
		$actual = $processor->process( $input );
		$this->assertEquals( $expected, $actual );
	}
}
