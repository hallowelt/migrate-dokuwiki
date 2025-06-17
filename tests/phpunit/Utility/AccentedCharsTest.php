<?php

namespace HalloWelt\MigrateDokuwiki\Tests\Utility;

use HalloWelt\MigrateDokuwiki\Utility\AccentedChars;
use PHPUnit\Framework\TestCase;

class AccentedCharsTest extends TestCase {
	/**
	 * @covers \HalloWelt\MigrateDokuwiki\Utility\AccentedChars::normalizeAccentedText()
	 */
	public function testNormalizeAccentedText() {
		$text = 'loräm:ipsüm:dölör';
		$text = AccentedChars::normalizeAccentedText( $text );
		$this->assertEquals( 'loraem:ipsuem:doeloer', $text );
	}
}
