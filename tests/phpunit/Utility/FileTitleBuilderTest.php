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
		$actualTitles = $this->doTest( $titleBuilder, $pages );
		$expectedTitles = $this->getExpectedTitles();
		$this->assertEquals( $expectedTitles, $actualTitles );

		// attic revision titles
		$pages = $this->getAtticPageFilePaths();
		$actualTitles = $this->doTest( $titleBuilder, $pages, true );
		$expectedTitles = $this->getExpectedTitles();
		$this->assertEquals( $expectedTitles, $actualTitles );

		$config = [
			'ext-ns-file-repo-compat' => true,
		];

		// latest revision title
		$pages = $this->getPageFilePaths();
		$actualTitles = $this->doTest( $titleBuilder, $pages, false, $config );
		$expectedTitles = $this->getExpectedTitlesWithFileRepoCompatibility();
		$this->assertEquals( $expectedTitles, $actualTitles );

		// attic revision titles
		$pages = $this->getAtticPageFilePaths();
		$actualTitles = $this->doTest( $titleBuilder, $pages, true, $config );
		$expectedTitles = $this->getExpectedTitlesWithFileRepoCompatibility();
		$this->assertEquals( $expectedTitles, $actualTitles );

		$config = [
			'space-prefix' => [
				'tools' => 'MyNamespace:',
			]
		];

		// latest revision title
		$pages = $this->getPageFilePaths();
		$actualTitles = $this->doTest( $titleBuilder, $pages, false, $config );
		$expectedTitles = $this->getExpectedTitlesMappedNamespace();
		$this->assertEquals( $expectedTitles, $actualTitles );

		// attic revision titles
		$pages = $this->getAtticPageFilePaths();
		$actualTitles = [];
		$actualTitles = $this->doTest( $titleBuilder, $pages, true, $config );
		$expectedTitles = $this->getExpectedTitlesMappedNamespace();
		$this->assertEquals( $expectedTitles, $actualTitles );

		$config = [
			'space-prefix' => [
				'tools' => 'MyNamespace:',
			],
			'ext-ns-file-repo-compat' => true,
		];

		// latest revision title
		$pages = $this->getPageFilePaths();
		$actualTitles = $this->doTest( $titleBuilder, $pages, false, $config );
		$expectedTitles = $this->getExpectedTitlesMappedNamespaceNSFileRepoCombatibility();
		$this->assertEquals( $expectedTitles, $actualTitles );

		// attic revision titles
		$pages = $this->getAtticPageFilePaths();
		$actualTitles = [];
		$actualTitles = $this->doTest( $titleBuilder, $pages, true, $config );
		$expectedTitles = $this->getExpectedTitlesMappedNamespaceNSFileRepoCombatibility();
		$this->assertEquals( $expectedTitles, $actualTitles );

	}

	/**
	 * @param FileTitleBuilder $titleBuilder
	 * @param array $pages
	 * @param bool $history
	 * @param array $config
	 * @return array
	 */
	private function doTest(
		FileTitleBuilder $titleBuilder, array $pages, bool $history = false, array $config = []
	): array {
		$actualTitles = [];
		foreach ( $pages as $filepath ) {
			$paths = explode( '/', trim( $filepath, '/' ) );
			$actualTitles[] = $titleBuilder->build( $paths, $history, $config );
		}
		return $actualTitles;
	}

	/**
	 * @return array
	 */
	private function getPageFilePaths(): array {
		return [
			'test.png',
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
			'test.20250624.png',
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
			'Test.png',
			'Projects_Types_Ab_type_01.png',
			'Tools_Toolbox_Wrench.pdf',
			'Tools_Toolbox_Hammer_01.csv',
			'Box-a_Item-01.jpg',
		];
	}

	/**
	 * @return array
	 */
	private function getExpectedTitlesWithFileRepoCompatibility(): array {
		return [
			'Test.png',
			'Projects:Types_Ab_type_01.png',
			'Tools:Toolbox_Wrench.pdf',
			'Tools:Toolbox_Hammer_01.csv',
			'Box_a:Item-01.jpg',
		];
	}

	/**
	 * @return array
	 */
	private function getExpectedTitlesMappedNamespace(): array {
		return [
			'Test.png',
			'Projects_Types_Ab_type_01.png',
			'MyNamespace_Toolbox_Wrench.pdf',
			'MyNamespace_Toolbox_Hammer_01.csv',
			'Box-a_Item-01.jpg',
		];
	}

	/**
	 * @return array
	 */
	private function getExpectedTitlesMappedNamespaceNSFileRepoCombatibility(): array {
		return [
			'Test.png',
			'Projects:Types_Ab_type_01.png',
			'MyNamespace:Toolbox_Wrench.pdf',
			'MyNamespace:Toolbox_Hammer_01.csv',
			'Box_a:Item-01.jpg',
		];
	}

}
