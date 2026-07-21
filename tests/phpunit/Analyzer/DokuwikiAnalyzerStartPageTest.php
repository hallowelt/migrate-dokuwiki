<?php

namespace HalloWelt\MigrateDokuwiki\Tests\Analyzer;

use HalloWelt\MediaWiki\Lib\Migration\DataBuckets;
use HalloWelt\MediaWiki\Lib\Migration\Workspace;
use HalloWelt\MigrateDokuwiki\Analyzer\DokuwikiAnalyzer;
use PHPUnit\Framework\TestCase;
use SplFileInfo;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Tests the three cases for DokuWiki's start.txt namespace main-page convention:
 *
 *  1. Only foo/start.txt  → treated as namespace main page (key foo:foo, alias stored)
 *  2. Only foo.txt        → existing namespace main-page behaviour unchanged (key foo:foo)
 *  3. Both foo.txt AND foo/start.txt → foo.txt stays foo:foo, start.txt becomes foo:start
 *
 * @covers \HalloWelt\MigrateDokuwiki\Analyzer\DokuwikiAnalyzer
 */
class DokuwikiAnalyzerStartPageTest extends TestCase {

	/** @var string */
	private $tmpDir;

	protected function setUp(): void {
		$this->tmpDir = sys_get_temp_dir() . '/dw-analyzer-test-' . uniqid();
		mkdir( $this->tmpDir, 0777, true );
	}

	protected function tearDown(): void {
		$this->removeDir( $this->tmpDir );
	}

	// -------------------------------------------------------------------------
	// Tests
	// -------------------------------------------------------------------------

	/**
	 * foo/start.txt alone → treated as namespace main page.
	 * pages-map key must be foo:foo; alias foo:start → foo:foo must be stored.
	 */
	public function testOnlyStartTxtIsNamespaceMainPage(): void {
		$src = $this->makeSrc( [
			'pages/foo/start.txt' => 'content',
		] );

		[ $pagesMap, $aliases ] = $this->runAnalyzer( $src, [ 'pages/foo/start.txt' ] );

		$this->assertArrayHasKey( 'foo:foo', $pagesMap, 'start.txt alone must be stored under foo:foo' );
		$this->assertArrayNotHasKey( 'foo:start', $pagesMap, 'foo:start must not appear in pages-map when there is no conflict' );
		$this->assertSame( 'foo:foo', $aliases['foo:start'] ?? null, 'alias foo:start → foo:foo must be stored' );
	}

	/**
	 * foo.txt with a foo/ directory → existing behaviour: namespace main page.
	 * pages-map key must be foo:foo; no aliases must be stored.
	 */
	public function testOnlyFooTxtIsNamespaceMainPage(): void {
		$src = $this->makeSrc( [
			'pages/foo.txt'       => 'content',
			'pages/foo/.keep'     => '',   // the foo/ directory must exist for namespaceMainpage
		] );

		[ $pagesMap, $aliases ] = $this->runAnalyzer( $src, [ 'pages/foo.txt' ] );

		$this->assertArrayHasKey( 'foo:foo', $pagesMap, 'foo.txt with foo/ dir must be stored under foo:foo' );
		$this->assertEmpty( $aliases, 'no aliases expected when only foo.txt is present' );
	}

	/**
	 * Both foo.txt and foo/start.txt present → foo.txt keeps foo:foo,
	 * start.txt becomes the distinct page foo:start; no alias stored.
	 */
	public function testBothPresentCreatesDistinctStartPage(): void {
		$src = $this->makeSrc( [
			'pages/foo.txt'       => 'content',
			'pages/foo/start.txt' => 'start content',
		] );

		[ $pagesMap, $aliases ] = $this->runAnalyzer( $src, [ 'pages/foo.txt', 'pages/foo/start.txt' ] );

		$this->assertArrayHasKey( 'foo:foo', $pagesMap, 'foo.txt must be stored under foo:foo' );
		$this->assertArrayHasKey( 'foo:start', $pagesMap, 'start.txt must be stored under foo:start when both exist' );
		$this->assertEmpty( $aliases, 'no alias expected when both foo.txt and foo/start.txt are present' );
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Creates the source directory tree and returns its path.
	 *
	 * @param array $files  relative path => content
	 * @return string absolute path to the source root
	 */
	private function makeSrc( array $files ): string {
		$src = $this->tmpDir . '/src';
		foreach ( $files as $rel => $content ) {
			$abs = $src . '/' . $rel;
			mkdir( dirname( $abs ), 0777, true );
			file_put_contents( $abs, $content );
		}
		return $src;
	}

	/**
	 * Instantiates and runs the analyzer for the given relative file paths,
	 * then returns [pages-map, start-page-aliases].
	 *
	 * @param string $src       absolute source root
	 * @param string[] $relPaths  paths relative to $src to analyze
	 * @return array{0: array, 1: array}
	 */
	private function runAnalyzer( string $src, array $relPaths ): array {
		$wsDir = $this->tmpDir . '/workspace';
		mkdir( $wsDir, 0777, true );
		$workspace = new Workspace( new SplFileInfo( $wsDir ) );

		$buckets = new DataBuckets( [] );
		$analyzer = DokuwikiAnalyzer::factory( [], $workspace, $buckets );
		$analyzer->setSourcePath( $src . '/' );
		$analyzer->setOutput( new BufferedOutput() );

		foreach ( $relPaths as $rel ) {
			$analyzer->analyze( new SplFileInfo( $src . '/' . $rel ) );
		}

		// Read back from workspace (the analyzer saves after each file)
		$result = new DataBuckets( [ 'pages-map', 'start-page-aliases' ] );
		$result->loadFromWorkspace( $workspace );

		return [
			$result->getBucketData( 'pages-map' ),
			$result->getBucketData( 'start-page-aliases' ),
		];
	}

	/**
	 * Recursively removes a directory.
	 */
	private function removeDir( string $dir ): void {
		if ( !is_dir( $dir ) ) {
			return;
		}
		foreach ( scandir( $dir ) as $entry ) {
			if ( $entry === '.' || $entry === '..' ) {
				continue;
			}
			$path = $dir . '/' . $entry;
			is_dir( $path ) ? $this->removeDir( $path ) : unlink( $path );
		}
		rmdir( $dir );
	}
}
