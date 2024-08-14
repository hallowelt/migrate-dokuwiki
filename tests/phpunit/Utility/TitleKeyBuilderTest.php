<?php

namespace HalloWelt\MigrateDokuwiki\Tests\Utility;

use HalloWelt\MigrateDokuwiki\Utility\TitleKeyBuilder;
use PHPUnit\Framework\TestCase;

class TitleKeyBuilderTest extends TestCase {
	/**
	 * @covers \HalloWelt\MigrateDokuwiki\Utility\TitleKeyBuilder::build()
	 */
	public function testBuild() {
		$titleBuilder = new TitleKeyBuilder();

		// latest revision title
		$pages = $this->getPageFilePaths();
		$actualTitles = [];
		foreach ( $pages as $filepath ) {
			$paths = explode( '/', trim( $filepath, '/' ) );
			$actualTitles[] = $titleBuilder->build( $paths );
		}
		$expectedTitles = $this->getExpectedTitles();
		$this->assertEquals( $expectedTitles, $actualTitles );
	}

	/**
	 * @return array
	 */
	private function getPageFilePaths(): array {
		return [
			'start.txt',
			'projects/types/ab.type_01.txt',
			'tools/toolbox/wrench.txt',
			'tools/toolbox/hammer.01.txt',
		];
	}

	/**
	 * @return array
	 */
	private function getExpectedTitles(): array {
		return [
			'start',
			'projects:types:ab.type_01',
			'tools:toolbox:wrench',
			'tools:toolbox:hammer.01',
		];
	}

}
