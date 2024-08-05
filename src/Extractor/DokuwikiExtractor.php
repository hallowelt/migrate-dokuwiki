<?php

namespace HalloWelt\MigrateDokuwiki\Extractor;

use DOMDocument;
use DOMElement;
use HalloWelt\MediaWiki\Lib\Migration\DataBuckets;
use HalloWelt\MediaWiki\Lib\Migration\IOutputAwareInterface;
use HalloWelt\MediaWiki\Lib\Migration\Workspace;
use HalloWelt\MigrateDokuwiki\IExtractor;
use HalloWelt\MigrateConfluence\Utility\XMLHelper;
use SplFileInfo;

class DokuwikiExtractor implements IExtractor, IOutputAwareInterface {


	/** @var Input\InputInterface */
	protected $input = null;

	/** @var OutputInterface */
	protected $output = null;

	/** @var array */
	protected $config = [];

	/** @var Workspace */
	protected $workspace = null;

	/** @var DataBuckets */
	protected $buckets = null;

	/** @var array */
	private $categories = [];

	/**
	 * @param array $config
	 * @param Workspace $workspace
	 * @param DataBuckets $buckets
	 */
	public function __construct( $config, Workspace $workspace, DataBuckets $buckets ) {
		$this->config = $config;
		$this->workspace = $workspace;
		$this->buckets = $buckets;
		if ( isset( $this->config['config']['categories'] ) ) {
			$this->categories = $this->config['config']['categories'];
		}
	}

	/**
	 * @param array $config
	 * @param Workspace $workspace
	 * @param DataBuckets $buckets
	 * @return IExtractor
	 */
	public static function factory( $config, Workspace $workspace, DataBuckets $buckets ): IExtractor {
		return new static( $config, $workspace, $buckets );
	}

	/**
	 * @param OutputInterface $output
	 * @return void
	 */
	public function setOutput( $output ) {
		$this->output = $output;
	}

	/**
	 * @return bool
	 */
	public function extract(): bool {
		$result = $this->doExtract();
		return $result;
	}

	/**
	 * @param SplFileInfo $file
	 * @return bool
	 */
	protected function doExtract(): bool {
		$this->extractRevisions();
		
		return true;
	}

	/**
	 *
	 */
	private function extractRevisions() {
		$pagesMap = $this->buckets->getBucketData( 'pages-map' );
		$historyPageTitles = $this->buckets->getBucketData( 'attic-pages-map' );
		$pageTitles = $this->buckets->getBucketData( 'page-titles' );
		$pageChangesMap = $this->buckets->getBucketData( 'page-changes-map' );
		$pageMetaMap = $this->buckets->getBucketData( 'page-meta-map' );
		$titles = [];
		if ( isset( $pageTitles['pages_titles'] ) ) {
			$titles = $pageTitles['pages_titles'];
		}
		foreach ( $titles as $title ) {
			$id = $this->makeIdFromTitle( $title );

			if ( isset( $pagesMap[$title] ) && !empty( $pagesMap[$title] ) ) {
				$filepath = $pagesMap[$title][0];
				if ( !file_exists( $filepath ) ) {
					continue;
				}
				$this->extractCurrentPageRevision( $title, $filepath, $id );
			} else {
				continue;
			}

			if ( isset( $historyPageTitles[$title] ) && !empty( $historyPageTitles[$title] ) ) {
				foreach ( $historyPageTitles[$title] as $filepath ) {
					if ( !file_exists( $filepath ) ) {
						continue;
					}
					$this->extractHistoryPageRevision( $title, $filepath, $id );
				}
			}

			if ( isset( $pageChangesMap[$title] ) && !empty( $pageChangesMap[$title] ) ) {
				$filepath = $pageChangesMap[$title][0];
				if ( !file_exists( $filepath ) ) {
					//$this->extractPageChanges( $title, $filepath, $id );
				}
			}

			if ( isset( $pageMetaMap[$title] ) && !empty( $pageMetaMap[$title] ) ) {
				$filepath = $pageMetaMap[$title][0];
				if ( file_exists( $filepath ) ) {
					//$this->extractPageMeta( $title, $filepath, $id );
				}
			}
		}
		return;

		$mediaMap = $this->buckets->getBucketData( 'media-map' );
		$historyMediaTitles = $this->buckets->getBucketData( 'attic-media-map' );
		$mediaTitles = $this->buckets->getBucketData( 'media-titles' );
		$media = [];
		if ( isset( $mediaTitles['media_titles'] ) ) {
			$media = $mediaTitles['media_titles'];
		}
		foreach ( $media as $fileTitle ) {
			if ( isset( $mediaMap[$fileTitle] ) && !empty( $mediaMap[$fileTitle] ) ) {
				$filepath = $mediaMap[$fileTitle][0];
				if ( !file_exists( $filepath ) ) {
					continue;
				}
				$this->extractCurrentMediaRevision( $fileTitle, $filepath, $id );
			} else {
				continue;
			}

			if ( isset( $historyMediaTitles[$fileTitle] ) && !empty( $historyMediaTitles[$fileTitle] ) ) {
				foreach ( $historyMediaTitles[$fileTitle] as $filepath ) {
					if ( !file_exists( $filepath ) ) {
						continue;
					}
					$this->extractHistoryMediaRevision( $fileTitle, $filepath, $id );
				}
			}

		}
	}

	/**
	 * @param string $title
	 * @param string $filepath
	 * @param string $id
	 */
	private function extractCurrentPageRevision( string $title, string $filepath, string $id ) {
		$content = file_get_contents( $filepath );
		$targetFileName = $this->workspace->saveRawContent( $id, $content );
		$this->buckets->addData( 'page-id-to-title-map', $id, $title, true, true );
		$this->buckets->addData( 'page-id-to-page-contents', $id, $targetFileName, true, true );
		$this->output->writeln( "\t - Extract current revision for title: $title" );
	}

	/**
	 * @param string $title
	 * @param string $filepath
	 * @param string $id
	 */
	private function extractHistoryPageRevision( string $title, string $filepath, string $id ) {
		$filenameParts = $this->getFilenameParts( $filepath );
		if ( !$this->isValidHistoryVersion( $filenameParts ) ) {
			return;
		}

		$timestamp = $this->getTimestampOfHistoryVersion( $filenameParts );
		$filename = $this->makeFilenameForHistoryVersion( $filenameParts );
		$content = file_get_contents( $filepath );
		$targetFileName = $this->workspace->saveRawContent( $filename, $content, 'content/history/raw/' . md5( $title ) );
		$this->buckets->addData( 'page-id-to-attic-page-contents', $id, $targetFileName, true, true );
		$this->output->writeln( "\t - Extract history revision for title: $title from " . $this->getHumanReadableTimestamp( $timestamp ) );
	}

	/**
	 * @param string $title
	 * @param string $filepath
	 * @param string $id
	 */
	private function extractPageChanges( string $title, string $filepath, string $id ) {
		$id = $this->makeIdFromTitle( $title );
		$this->buckets->addData( 'page-id-to-page-changes-map', $id, $targetFileName, true, true );
		$this->output->writeln( $title );
	}

	/**
	 * @param string $title
	 * @param string $filepath
	 * @param string $id
	 */
	private function extractCurrentMediaRevision( string $title, string $filepath, string $id ) {
		$content = file_get_contents( $filepath );
		$targetFileName = $this->workspace->saveUploadFile( $title, $content );
		$this->buckets->addData( 'media-id-to-title-map', $id, $title, true, true );
		$this->buckets->addData( 'media-id-to-media-contents', $id, $targetFileName, true, true );
		$this->output->writeln( "\t - Extract current revision for media: $title" );
	}

	/**
	 * @param string $title
	 * @param string $filepath
	 * @param string $id
	 */
	private function extractHistoryMediaRevision( string $title, string $filepath, string $id ) {
		$filenameParts = $this->getFilenameParts( $filepath );
		if ( !$this->isValidHistoryVersion( $filenameParts ) ) {
			return;
		}
		$timestamp = $this->getTimestampOfHistoryVersion( $filenameParts );
		$filename = $this->makeFilenameForHistoryVersion( $filenameParts );
		$content = file_get_contents( $filepath );
		$targetFileName = $this->workspace->saveUploadFile( $filename, $content, 'content/history/images/' . $id );
		$this->buckets->addData( 'media-id-to-attic-media-contents', $id, $targetFileName, true, true );
		$this->output->writeln( "\t - Extract history revision for media: $title from " . $this->getHumanReadableTimestamp( $timestamp ) );
	}

	/**
	 * @param string $id
	 * @return string
	 */
	private function makeIdFromTitle( string $title ) {
		return md5( $title );
	}

	/**
	 * @param string $filepath
	 * @return array
	 */
	private function getFilenameParts( string $filepath ): array {
		$file = new SplFileInfo ( $filepath );
		$filename = $file->getFilename();
		return explode( '.', $filename );
	}

	/**
	 * @param array $paths
	 * @return bool
	 */
	private function isValidHistoryVersion( array $filenameParts ): bool {
		if ( count( $filenameParts ) < 3 ) {
			// A valid history version has at least name.timestamp.extension
			return false;
		}
		return true;
	}

	/**
	 * @param array $paths
	 * @return string
	 */
	private function getTimestampOfHistoryVersion( array $paths ): string {
		$fileExtension = array_pop( $paths );
		$timestamp = array_pop( $paths );
		return $timestamp;
	}

	/**
	 * @param array $paths
	 * @return string
	 */
	private function makeFilenameForHistoryVersion( array $paths ): string {
		$timestamp = $this->getTimestampOfHistoryVersion( $paths );
		$fileExtension = array_pop( $paths );
		return "$timestamp.$fileExtension";
	}

	/**
	 * @param string $timestamp
	 * @return string
	 */
	private function getHumanReadableTimestamp( string $timestamp ): string {
		return date( 'Y-m-d H:i:s', $timestamp );
	}
}