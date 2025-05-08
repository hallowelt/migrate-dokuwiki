<?php

namespace HalloWelt\MigrateDokuwiki\Tests\Converter\PostProcessor;

use HalloWelt\MigrateDokuwiki\Converter\PostProcessors\Displaytitle;
use PHPUnit\Framework\TestCase;

class DisplaytitleTest extends TestCase {
	/**
	 * @covers \HalloWelt\MigrateDokuwiki\Converter\PostProcessor\Displaytitle::process()
	 */
	public function testProcess() {
		$processor = new Displaytitle();
		$input = <<<TEXT
<span id="test"></span>
= Test =
lorem ipsum dolor
TEXT;

		$expected = <<<TEXT
<span id="test"></span>
{{DISPLAYTITLE:Test}}
lorem ipsum dolor
TEXT;
		$actual = $processor->process( $input );
		$this->assertEquals( $expected, $actual );

		$input = <<<TEXT
<span id="new-test"></span>
== New test ==
lorem ipsum dolor
TEXT;

		$expected = <<<TEXT
<span id="new-test"></span>
{{DISPLAYTITLE:New test}}
lorem ipsum dolor
TEXT;
		$actual = $processor->process( $input );
		$this->assertEquals( $expected, $actual );
	}
}
