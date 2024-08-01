<?php

namespace HalloWelt\MigrateDokuwiki\Tests\Utility;

use DOMDocument;
use HalloWelt\MigrateDokuwiki\Utility\TitleBuilder;
use PHPUnit\Framework\TestCase;

class TitleBuilderTest extends TestCase {
	/**
	 * @covers \HalloWelt\MigrateDokuwiki\Utility\TitleBuilder::build()
	 */
	public function testBuild() {
		$titleBuilder = new TitleBuilder();

		// latest revision title
		$pages = $this->getPageFilePaths();
		$actualTitles = [];
		foreach ( $pages as $filepath ) {
			$paths = explode( '/', trim( $filepath, '/' ) );
			$actualTitles[] = $titleBuilder->build( $paths );
		}
		$expectedTitles = $this->getExpectedTitles();
		$this->assertEquals( $expectedTitles, $actualTitles );

		// attic revision titles
		$pages = $this->getAtticPageFilePaths();
		$actualTitles = [];
		foreach ( $pages as $filepath ) {
			$paths = explode( '/', trim( $filepath, '/' ) );
			$actualTitles[] = $titleBuilder->build( $paths, true );
		}
		$expectedTitles = $this->getExpectedTitles();
		$this->assertEquals( $expectedTitles, $actualTitles );
	}

	/**
	 * @retrun array
	 */
	private function getPageFilePaths(): array {
		return [
			'projects/types/ab.type_01.txt',
			'tools/toolbox/wrench.txt',
			'tools/toolbox/hammer.01.txt',
		];
	}

	/**
	 * @retrun array
	 */
	private function getExpectedTitles(): array {
		return [
			'Projects:Types/Ab.type_01',
			'Tools:Toolbox/Wrench',
			'Tools:Toolbox/Hammer.01',
		];
	}

	/**
	 * @retrun array
	 */
	private function getAtticPageFilePaths(): array {
		return [
			'projects/types/ab.type_01.20240730.txt',
			'tools/toolbox/wrench.20240730.txt',
			'tools/toolbox/hammer.01.20240730.txt',
		];
	}

}