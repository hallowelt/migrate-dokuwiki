<?php

namespace HalloWelt\MigrateDokuwiki\Tests\Utility;

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

		$config = [
			'space-prefix' => [
				'tools' => "MyTools",
				"box-a" => "MyBox_A"
			]
		];

		$actualTitles = [];
		foreach ( $pages as $filepath ) {
			$paths = explode( '/', trim( $filepath, '/' ) );
			$actualTitles[] = $titleBuilder->build( $paths, true, $config );
		}
		$expectedTitles = $this->getExpectedTitlesMappedPrefix();
		$this->assertEquals( $expectedTitles, $actualTitles );

		$config = [
			'space-prefix' => [
				'tools' => "MyTools",
				"box-a" => "MyBox_A"
			],
			'keep-mapped-prefix' => true
		];

		$actualTitles = [];
		foreach ( $pages as $filepath ) {
			$paths = explode( '/', trim( $filepath, '/' ) );
			$actualTitles[] = $titleBuilder->build( $paths, true, $config );
		}
		$expectedTitles = $this->getExpectedTitlesMappedPrefixKeepingPrefix();
		$this->assertEquals( $expectedTitles, $actualTitles );
	}

	/**
	 * @return array
	 */
	private function getPageFilePaths(): array {
		return [
			'start.txt',
			'__test.txt',
			'projects/types/ab.type_01.txt',
			'tools/toolbox/wrench.txt',
			'tools/toolbox/hammer.01.txt',
			'box-a/item-01.txt',
		];
	}

	/**
	 * @return array
	 */
	private function getExpectedTitles(): array {
		return [
			'Start',
			'Test',
			'Projects:Types/Ab.type_01',
			'Tools:Toolbox/Wrench',
			'Tools:Toolbox/Hammer.01',
			'Box_a:Item-01',
		];
	}

	/**
	 * @return array
	 */
	private function getAtticPageFilePaths(): array {
		return [
			'start.20240730.txt',
			'___test.20240830.txt',
			'projects/types/ab.type_01.20240730.txt',
			'tools/toolbox/wrench.20240730.txt',
			'tools/toolbox/hammer.01.20240730.txt',
			'box-a/item-01.20240730.txt',
		];
	}

	/**
	 * @return array
	 */
	private function getExpectedTitlesMappedPrefix(): array {
		return [
			'Start',
			'Test',
			'Projects:Types/Ab.type_01',
			'MyTools:Toolbox/Wrench',
			'MyTools:Toolbox/Hammer.01',
			'MyBox_A:Item-01',
		];
	}

	/**
	 * @return array
	 */
	private function getExpectedTitlesMappedPrefixKeepingPrefix(): array {
		return [
			'Start',
			'Test',
			'Projects:Types/Ab.type_01',
			'MyTools:Tools/Toolbox/Wrench',
			'MyTools:Tools/Toolbox/Hammer.01',
			'MyBox_A:Box-a/Item-01',
		];
	}
}
