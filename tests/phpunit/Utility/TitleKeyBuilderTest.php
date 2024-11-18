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
		$actualKeys = [];
		foreach ( $pages as $filepath ) {
			$paths = explode( '/', trim( $filepath, '/' ) );
			$actualKeys[] = $titleBuilder->build( $paths );
		}
		$expectedKeys = $this->getExpectedKeys();
		$this->assertEquals( $expectedKeys, $actualKeys );

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
			'tools/toolbox 01/wrench 01.txt',
			'tools/toolbox/hammer.01.txt',
		];
	}

	/**
	 * @return array
	 */
	private function getExpectedKeys(): array {
		return [
			'start',
			'projects:types:ab.type_01',
			'tools:toolbox:wrench',
			'tools:toolbox_01:wrench_01',
			'tools:toolbox:hammer.01',
		];
	}

	/**
	 * @return array
	 */
	private function getExpectedDoubleKeys(): array {
		return [
			'start:start',
			'projects:types:ab.type_01:ab.type_01',
			'tools:toolbox:wrench:wrench',
			'tools:toolbox_01:wrench_01:wrench_01',
			'tools:toolbox:hammer.01:hammer.01',
		];
	}

}
