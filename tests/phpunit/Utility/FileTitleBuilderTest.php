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

		$pages = $this->getPageFilePaths();
		$atticPages = $this->getAtticPageFilePaths();
		$pageIdToTitleMap = $this->getPageIdToTitleMap();

		/**
		 * pageIdToTileMap empty
		 * nsfilerepo disabled
		 * no prefix config
		 */
		$this->runTest_1( $titleBuilder, $pages, $atticPages );

		/**
		 * pageIdToTileMap empty
		 * nsfilerepo enabled
		 * no prefix config
		 */
		$this->runTest_2( $titleBuilder, $pages, $atticPages );

		/**
		 * pageIdToTileMap empty
		 * nsfilerepo disabled
		 * with prefix config
		 */
		$this->runTest_3( $titleBuilder, $pages, $atticPages );

		/**
		 * pageIdToTileMap empty
		 * nsfilerepo enabled
		 * with prefix config
		 */
		$this->runTest_4( $titleBuilder, $pages, $atticPages );

		/**
		 * pageIdToTileMap not empty
		 * nsfilerepo disabled
		 * no prefix config
		 */
		$this->runTest_5( $titleBuilder, $pages, $atticPages, $pageIdToTitleMap  );
		
		/**
		 * pageIdToTileMap not empty
		 * nsfilerepo enabled
		 * no prefix config
		 */
		$this->runTest_6( $titleBuilder, $pages, $atticPages, $pageIdToTitleMap  );

		/**
		 * pageIdToTileMap not empty
		 * nsfilerepo disabled
		 * with prefix config
		 */
		$this->runTest_7( $titleBuilder, $pages, $atticPages, $pageIdToTitleMap  );

		/**
		 * pageIdToTileMap not empty
		 * nsfilerepo enabled
		 * with prefix config
		 */
		$this->runTest_8( $titleBuilder, $pages, $atticPages, $pageIdToTitleMap  );
	}

	/**
	 * @param FileTitleBuilder $titleBuilder
	 * @param array $pages
	 * @param array $pageIdToTitleMap
	 * @param bool $history
	 * @param array $config
	 * @return array
	 */
	private function doTest(
		FileTitleBuilder $titleBuilder, array $pages, array $pageIdToTitleMap, bool $history = false, array $config = []
	): array {
		$actualTitles = [];
		foreach ( $pages as $filepath ) {
			$paths = explode( ':', trim( $filepath, ':' ) );
			$actualTitles[] = $titleBuilder->build( $paths, $history, $pageIdToTitleMap, $config );
		}
		return $actualTitles;
	}

	/**
	 * @return array
	 */
	private function getPageIdToTitleMap(): array {
		return [
			'projects' => 'P-Projcects',
			'projects:types' => 'P-Projcects:P-Types',
			'tools' => 'P-Tools',
			'tools:toolbox' => 'P-Tools:P-Toolbox',
			'box-a' => 'P-Box-Alpha',
		];
	}

	/**
	 * @return array
	 */
	private function getPageFilePaths(): array {
		return [
			'test.png',
			'projects:types:ab.type_01.png',
			'tools:toolbox:wrench.pdf',
			'tools:toolbox:hammer.01.csv',
			'box-a:item-01.jpg',
		];
	}

	/**
	 * @return array
	 */
	private function getAtticPageFilePaths(): array {
		return [
			'test.20250624.png',
			'projects:types:ab.type_01.20240730.png',
			'tools:toolbox:wrench.20240730.pdf',
			'tools:toolbox:hammer.01.20240730.csv',
			'box-a:item-01.20240730.jpg',
		];
	}

	/**
	 * pageIdToTileMap empty
	 * nsfilerepo disabled
	 * no prefix config
	 *
	 * @param FileTitleBuilder $titleBuilder
	 * @param array $pages
	 * @param array $atticPages
	 * @return void
	 */
	private function runTest_1( FileTitleBuilder $titleBuilder, array $pages, array $atticPages ) {
		$expectedTitles = [
			'Test.png',
			'Projects_Types_Ab_type_01.png',
			'Tools_Toolbox_Wrench.pdf',
			'Tools_Toolbox_Hammer_01.csv',
			'Box-a_Item-01.jpg',
		];
		// latest revision title
		$actualTitles = $this->doTest( $titleBuilder, $pages, [] );
		$this->assertEquals( $expectedTitles, $actualTitles );

		// attic revision titles
		$actualTitles = $this->doTest( $titleBuilder, $atticPages, [], true );
		$this->assertEquals( $expectedTitles, $actualTitles );
	}

	/**
	 * pageIdToTileMap empty
	 * nsfilerepo enabled
	 * with prefix config
	 * 
	 * @param FileTitleBuilder $titleBuilder
	 * @param array $pages
	 * @param array $atticPages
	 * @return void
	 */
	private function runTest_2( FileTitleBuilder $titleBuilder, array $pages, array $atticPages ) {
		$expectedTitles = [
			'Test.png',
			'Projects:Types_Ab_type_01.png',
			'Tools:Toolbox_Wrench.pdf',
			'Tools:Toolbox_Hammer_01.csv',
			'Box_a:Item-01.jpg',
		];

		$config = [
			'ext-ns-file-repo-compat' => true,
		];

		// latest revision title
		$actualTitles = $this->doTest( $titleBuilder, $pages, [], false, $config );
		$this->assertEquals( $expectedTitles, $actualTitles );

		// attic revision titles
		$actualTitles = $this->doTest( $titleBuilder, $atticPages, [], true, $config );
		$this->assertEquals( $expectedTitles, $actualTitles );
	}

	/**
	 * pageIdToTileMap empty
	 * nsfilerepo disabled
	 * with prefix config
	 *
	 * @param FileTitleBuilder $titleBuilder
	 * @param array $pages
	 * @param array $atticPages
	 * @return void
	 */
	private function runTest_3( FileTitleBuilder $titleBuilder, array $pages, array $atticPages ) {
		$expectedTitles = [
			'Test.png',
			'MyNamespace_MyProjects_Types_Ab_type_01.png',
			'MyNamespace_Toolbox_Wrench.pdf',
			'MyNamespace_Toolbox_Hammer_01.csv',
			'Box-a_Item-01.jpg',
		];

		$config = [
			'space-prefix' => [
				'projects' => 'MyNamespace:MyProjects/',
				'tools' => 'MyNamespace:',
			]
		];
		// latest revision title
		$actualTitles = $this->doTest( $titleBuilder, $pages, [], false, $config );
		$this->assertEquals( $expectedTitles, $actualTitles );

		// attic revision titles
		$actualTitles = $this->doTest( $titleBuilder, $atticPages, [], true, $config );
		$this->assertEquals( $expectedTitles, $actualTitles );
	}

	/**
	 * pageIdToTileMap empty
	 * nsfilerepo enabled
	 * no prefix config
	 * 
	 * @param FileTitleBuilder $titleBuilder
	 * @param array $pages
	 * @param array $atticPages
	 * @return void
	 */
	private function runTest_4( FileTitleBuilder $titleBuilder, array $pages, array $atticPages ) {
		$expectedTitles = [
			'Test.png',
			'MyNamespace:MyProjects_Types_Ab_type_01.png',
			'MyNamespace:Toolbox_Wrench.pdf',
			'MyNamespace:Toolbox_Hammer_01.csv',
			'Box_a:Item-01.jpg',
		];

		$config = [
			'ext-ns-file-repo-compat' => true,
			'space-prefix' => [
				'projects' => 'MyNamespace:MyProjects/',
				'tools' => 'MyNamespace:',
			]
		];

		// latest revision title
		$actualTitles = $this->doTest( $titleBuilder, $pages, [], false, $config );
		$this->assertEquals( $expectedTitles, $actualTitles );

		// attic revision titles
		$actualTitles = $this->doTest( $titleBuilder, $atticPages, [], true, $config );
		$this->assertEquals( $expectedTitles, $actualTitles );
	}

	/**
	 * pageIdToTileMap not empty
	 * nsfilerepo disabled
	 * no prefix config
	 *
	 * @param FileTitleBuilder $titleBuilder
	 * @param array $pages
	 * @param array $atticPages
	 * @param array $pageIdToTitleMap
	 * @return void
	 */
	private function runTest_5( FileTitleBuilder $titleBuilder, array $pages, array $atticPages, array $pageIdToTitleMap ) {
		$expectedTitles = [
			'Test.png',
			'P-Projcects_P-Types_Ab_type_01.png',
			'P-Tools_P-Toolbox_Wrench.pdf',
			'P-Tools_P-Toolbox_Hammer_01.csv',
			'P-Box-Alpha_Item-01.jpg',
		];
		// latest revision title
		$actualTitles = $this->doTest( $titleBuilder, $pages, $pageIdToTitleMap, false, [] );
		$this->assertEquals( $expectedTitles, $actualTitles );

		// attic revision titles
		$actualTitles = $this->doTest( $titleBuilder, $atticPages, $pageIdToTitleMap, true, [] );
		$this->assertEquals( $expectedTitles, $actualTitles );
	}

	/**
	 * pageIdToTileMap not empty
	 * nsfilerepo enabled
	 * no prefix config
	 *
	 * @param FileTitleBuilder $titleBuilder
	 * @param array $pages
	 * @param array $atticPages
	 * @param array $pageIdToTitleMap
	 * @return void
	 */
	private function runTest_6( FileTitleBuilder $titleBuilder, array $pages, array $atticPages, array $pageIdToTitleMap ) {
		$expectedTitles = [
			'Test.png',
			'P_Projcects:P-Types_Ab_type_01.png',
			'P_Tools:P-Toolbox_Wrench.pdf',
			'P_Tools:P-Toolbox_Hammer_01.csv',
			'P_Box_Alpha:Item-01.jpg',
		];

		$config = [
			'ext-ns-file-repo-compat' => true,
		];

		// latest revision title
		$actualTitles = $this->doTest( $titleBuilder, $pages, $pageIdToTitleMap, false, $config );
		$this->assertEquals( $expectedTitles, $actualTitles );

		// attic revision titles
		$actualTitles = $this->doTest( $titleBuilder, $atticPages, $pageIdToTitleMap, true, $config );
		$this->assertEquals( $expectedTitles, $actualTitles );
	}

	/**
	 * pageIdToTileMap not empty
	 * nsfilerepo disabled
	 * with prefix config
	 *
	 * @param FileTitleBuilder $titleBuilder
	 * @param array $pages
	 * @param array $atticPages
	 * @param array $pageIdToTitleMap
	 * @return void
	 */
	private function runTest_7( FileTitleBuilder $titleBuilder, array $pages, array $atticPages, array $pageIdToTitleMap ) {
		$expectedTitles = [
			'Test.png',
			'P-Projcects_P-Types_Ab_type_01.png',
			'P-Tools_P-Toolbox_Wrench.pdf',
			'P-Tools_P-Toolbox_Hammer_01.csv',
			'P-Box-Alpha_Item-01.jpg',
		];

		$config = [
			'space-prefix' => [
				'projects' => 'MyNamespace:MyProjects/',
				'tools' => 'MyNamespace:',
			]
		];

		// latest revision title
		$actualTitles = $this->doTest( $titleBuilder, $pages, $pageIdToTitleMap, false, $config );
		$this->assertEquals( $expectedTitles, $actualTitles );

		// attic revision titles
		$actualTitles = $this->doTest( $titleBuilder, $atticPages, $pageIdToTitleMap, true, $config );
		$this->assertEquals( $expectedTitles, $actualTitles );
	}

	/**
	 * pageIdToTileMap not empty
	 * nsfilerepo enabled
	 * with prefix config
	 *
	 * @param FileTitleBuilder $titleBuilder
	 * @param array $pages
	 * @param array $atticPages
	 * @param array $pageIdToTitleMap
	 * @return void
	 */
	private function runTest_8( FileTitleBuilder $titleBuilder, array $pages, array $atticPages, array $pageIdToTitleMap ) {
		$expectedTitles = [
			'Test.png',
			'P_Projcects:P-Types_Ab_type_01.png',
			'P_Tools:P-Toolbox_Wrench.pdf',
			'P_Tools:P-Toolbox_Hammer_01.csv',
			'P_Box_Alpha:Item-01.jpg',
		];

		$config = [
			'ext-ns-file-repo-compat' => true,
			'space-prefix' => [
				'projects' => 'MyNamespace:MyProjects/',
				'tools' => 'MyNamespace:',
			]
		];

		// latest revision title
		$actualTitles = $this->doTest( $titleBuilder, $pages, $pageIdToTitleMap, false, $config );
		$this->assertEquals( $expectedTitles, $actualTitles );

		// attic revision titles
		$actualTitles = $this->doTest( $titleBuilder, $atticPages, $pageIdToTitleMap, true, $config );
		$this->assertEquals( $expectedTitles, $actualTitles );
	}
}
