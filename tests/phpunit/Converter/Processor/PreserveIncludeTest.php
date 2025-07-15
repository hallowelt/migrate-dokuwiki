<?php

namespace HalloWelt\MigrateDokuwiki\Tests\Converter\Processor;

use HalloWelt\MigrateDokuwiki\Converter\Processors\PreserveInclude;
use PHPUnit\Framework\TestCase;

class PreserveIncludeTest extends TestCase {
	/**
	 * @covers \HalloWelt\MigrateDokuwiki\Converter\Processor\PreserveInclude::process()
	 */
	public function testProcess() {
		$map = $this->getMap();
		$processor = new PreserveInclude( $map );

		$dataDir = dirname( __DIR__, 2 ) . '/data/Converter/Processor';
		$input = file_get_contents( "$dataDir/preserve-include-input.txt" );
		$expected = file_get_contents( "$dataDir/preserve-include-output.txt" );
		$actual = $processor->process( $input );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @return array
	 */
	private function getMap(): array {
		return [
			'start' => 'Start',
			'start:start' => 'Start',
			'projects:types:ab.type_01' => 'Projects:Types/Ab.type_01',
			'projects:types:ab.type_01:ab.type_01' => 'Projects:Types/Ab.type_01',
			'tools' => 'Tools:Tools',
			'tools:tools' => 'Tools:Tools',
			'tools:toolbox' => 'Tools:Toolbox',
			'tools:toolbox:toolbox' => 'Tools:Toolbox',
			'tools:toolbox:wrench' => 'Tools:Toolbox/Wrench',
			'tools:toolbox:wrench:wrench' => 'Tools:Toolbox/Wrench',
			'tools:toolbox:hammer.01' => 'Tools:Toolbox/Hammer.01',
			'tools:toolbox:screwdriver' => 'Tools:Toolbox/Screwdriver',
			'tools:toolbox:screwdriver:screwdriver' => 'Tools:Toolbox/Screwdriver',
			'ae:xyz:test' => 'Ä:Xyz/Test'
		];
	}

}
