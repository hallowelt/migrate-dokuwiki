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

		$config = [
			'space-prefix' => [
				'tools' => "MyTools:",
				"box-a" => "MyBox_A:TEST/"
			]
		];

		$actualTitles = [];
		foreach ( $pages as $filepath ) {
			$paths = explode( '/', trim( $filepath, '/' ) );
			$actualTitles[] = $titleBuilder->build( $paths, true, $config );
		}
		$expectedTitles = $this->getExpectedTitlesMappedPrefix();
		$this->assertEquals( $expectedTitles, $actualTitles );
	}

	/**
	 * @return array
	 */
	private function getPageFilePaths(): array {
		return [
			'start ',
			'__test ',
			'projects/projects',
			'projects/projects/subpage',
			'projects/types/ab.type_01',
			'tools/toolbox/wrench',
			'tools/toolbox/hammer.01',
			'box-a/item-01',
		];
	}

	/**
	 * @return array
	 */
	private function getExpectedTitles(): array {
		return [
			'Start',
			'Test',
			'Projects:Projects',
			'Projects:Projects/Subpage',
			'Projects:Types/Ab.type_01',
			'Tools:Toolbox/Wrench',
			'Tools:Toolbox/Hammer.01',
			'Box_a:Item-01',
		];
	}

	/**
	 * @return array
	 */
	private function getExpectedTitlesMappedPrefix(): array {
		return [
			'Start',
			'Test',
			'Projects:Projects',
			'Projects:Projects/Subpage',
			'Projects:Types/Ab.type_01',
			'MyTools:Toolbox/Wrench',
			'MyTools:Toolbox/Hammer.01',
			'MyBox_A:TEST/Item-01',
		];
	}
}
