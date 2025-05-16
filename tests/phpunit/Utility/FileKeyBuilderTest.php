<?php

namespace HalloWelt\MigrateDokuwiki\Tests\Utility;

use HalloWelt\MigrateDokuwiki\Utility\FileKeyBuilder;
use PHPUnit\Framework\TestCase;

class FileKeyBuilderTest extends TestCase {
	/**
	 * @covers \HalloWelt\MigrateDokuwiki\Utility\FileKeyBuilder::build()
	 */
	public function testBuild() {
		$titleBuilder = new FileKeyBuilder();

		// latest revision title
		$pages = $this->getPageFilePaths();

		$actualTitles = [];
		foreach ( $pages as $filepath ) {
			$paths = explode( '/', trim( $filepath, '/' ) );
			$actualTitles[] = $titleBuilder->build( $paths );
		}
		$expectedTitles = $this->getExpectedKeys();
		$this->assertEquals( $expectedTitles, $actualTitles );

		$actualKeys = [];
		foreach ( $pages as $filepath ) {
			$paths = explode( '/', trim( $filepath, '/' ) );
			$actualKeys[] = $titleBuilder->buildDoubleKey( $paths );
		}

		$expectedKeys = $this->getExpectedDoubleKeys();
		$this->assertEquals( $expectedKeys, $actualKeys );
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
	private function getExpectedKeys(): array {
		return [
			'start.txt',
			'projects:types:ab.type_01.txt',
			'tools:toolbox:wrench.txt',
			'tools:toolbox:hammer.01.txt',
		];
	}

	/**
	 * @return array
	 */
	private function getExpectedDoubleKeys(): array {
		return [
			'start:start.txt',
			'projects:types:ab.type_01:ab.type_01.txt',
			'tools:toolbox:wrench:wrench.txt',
			'tools:toolbox:hammer.01:hammer.01.txt',
		];
	}

}
