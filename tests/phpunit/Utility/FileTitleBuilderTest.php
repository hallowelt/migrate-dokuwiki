<?php

namespace HalloWelt\MigrateDokuwiki\Tests\Utility;

use HalloWelt\MigrateDokuwiki\Utility\FileTitleBuilder;
use PHPUnit\Framework\TestCase;

class FileTitleBuilderTest extends TestCase {
	/**
	 * @covers \HalloWelt\MigrateDokuwiki\Utility\FileTitleBuilder::build()
	 */
	public function testBuild() {
		$titleBuilder = new FileTitleBuilder();

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

		// latest revision title with NSFileRepo compatibility
		$pages = $this->getPageFilePaths();
		$actualTitles = [];
		foreach ( $pages as $filepath ) {
			$paths = explode( '/', trim( $filepath, '/' ) );
			$actualTitles[] = $titleBuilder->build( $paths, false, true );
		}
		$expectedTitles = $this->getExpectedTitlesWithNSFileRepoCompatibility();
		$this->assertEquals( $expectedTitles, $actualTitles );

		// attic revision titles with NSFileRepo compatibility
		$pages = $this->getAtticPageFilePaths();
		$actualTitles = [];
		foreach ( $pages as $filepath ) {
			$paths = explode( '/', trim( $filepath, '/' ) );
			$actualTitles[] = $titleBuilder->build( $paths, true, true );
		}
		$expectedTitles = $this->getExpectedTitlesWithNSFileRepoCompatibility();
		$this->assertEquals( $expectedTitles, $actualTitles );
	}

	/**
	 * @return array
	 */
	private function getPageFilePaths(): array {
		return [
			'projects/types/ab.type_01.png',
			'tools/toolbox/wrench.pdf',
			'tools/toolbox/hammer.01.csv',
			'box-a/item-01.jpg',
		];
	}

	/**
	 * @return array
	 */
	private function getAtticPageFilePaths(): array {
		return [
			'projects/types/ab.type_01.20240730.png',
			'tools/toolbox/wrench.20240730.pdf',
			'tools/toolbox/hammer.01.20240730.csv',
			'box-a/item-01.20240730.jpg',
		];
	}

	/**
	 * @return array
	 */
	private function getExpectedTitles(): array {
		return [
			'Projects_types_ab_type_01.png',
			'Tools_toolbox_wrench.pdf',
			'Tools_toolbox_hammer_01.csv',
			'Box_a_item-01.jpg',
		];
	}

	/**
	 * @return array
	 */
	private function getExpectedTitlesWithNSFileRepoCompatibility(): array {
		return [
			'Projects:Types_ab_type_01.png',
			'Tools:Toolbox_wrench.pdf',
			'Tools:Toolbox_hammer_01.csv',
			'Box_a:Item-01.jpg',
		];
	}
}
