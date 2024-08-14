<?php

namespace HalloWelt\MigrateDokuwiki\Tests\Converter\Processor;

use HalloWelt\MigrateDokuwiki\Converter\Processors\Link;
use PHPUnit\Framework\TestCase;

class LinkTest extends TestCase {
	/**
	 * @covers \HalloWelt\MigrateDokuwiki\Converter\Processor\Link::process()
	 */
	public function testProcess() {
		$map = $this->getMap();
		$processor = new Link( $map );

		$dataDir = dirname( __DIR__, 2 ) . '/data/Converter/Processor';
		$input = file_get_contents( "$dataDir/link-input.txt" );
		$expected = file_get_contents( "$dataDir/link-output.txt" );
		$actual = $processor->process( $input );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @return array
	 */
	private function getMap(): array {
		return [
			'start' => 'Start',
			'projects:types:ab.type_01' => 'Projects:Types/Ab.type_01',
			'tools:toolbox:wrench' => 'Tools:Toolbox/Wrench',
			'tools:toolbox:hammer.01' => 'Tools:Toolbox/Hammer.01',
		];
	}

}
