<?php

namespace HalloWelt\MigrateDokuwiki\Tests\Converter\Processor;

use HalloWelt\MigrateDokuwiki\Converter\Processors\Image;
use PHPUnit\Framework\TestCase;

class ImageTest extends TestCase {
	/**
	 * @covers \HalloWelt\MigrateDokuwiki\Converter\Processor\Image::process()
	 */
	public function testProcess() {
		$map = $this->getMap();
		$processor = new Image( $map );

		$dataDir = dirname( __DIR__, 2 ) . '/data/Converter/Processor';
		$input = file_get_contents( "$dataDir/image-input.txt" );
		$expected = file_get_contents( "$dataDir/image-output.txt" );
		$actual = $processor->process( $input );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @return array
	 */
	private function getMap(): array {
		return [
			'start.png' => 'Start.png',
			'projects:types:ab.type_01.png' => 'Projects_types_ab.type_01.png',
			'tools:toolbox:wrench.jpg' => 'Tools_toolbox_wrench.jpg',
			'tools:toolbox:hammer.01.svg' => 'Tools_toolbox_hammer.01.svg',
		];
	}

}
