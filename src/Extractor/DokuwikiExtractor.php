<?php

namespace HalloWelt\MigrateDokuwiki\Extractor;

use DOMDocument;
use DOMElement;
use HalloWelt\MediaWiki\Lib\Migration\DataBuckets;
use HalloWelt\MediaWiki\Lib\Migration\IOutputAwareInterface;
use HalloWelt\MediaWiki\Lib\Migration\Workspace;
use HalloWelt\MigrateDokuwiki\IExtractor;
use HalloWelt\MigrateDokuwiki\Utility\FilenameBuilder;
use HalloWelt\MigrateDokuwiki\Utility\TitleBuilder;
use SplFileInfo;
use StdClass;

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
		$pageTitles = $this->buckets->getBucketData( 'page-titles' );
		$mediaTitles = $this->buckets->getBucketData( 'media-titles' );

		$titles = [];
		if ( isset( $pageTitles['pages_titles'] ) ) {
			$titles = $pageTitles['pages_titles'];
		}

		$media = [];
		if ( isset( $mediaTitles['media_titles'] ) ) {
			$media = $mediaTitles['media_titles'];
		}

		#$this->extractCurrentPageRevisions( $titles );
		#$this->extractHistoryPageRevisions( $titles );
		#$this->extractCurrentMediaRevisions(  $media );
		#$this->extractHistoryMediaRevisions( $media );
		#$this->extractPageChanges( $titles );
		$this->extractPageMeta( $titles );
		
		return true;
	}

	/**
	 * @param array $titles
	 */
	private function extractCurrentPageRevisions( array $titles ) {
		$pagesMap = $this->buckets->getBucketData( 'pages-map' );
		
		$this->output->writeln( "Extract current revisons of page:" );

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
		}
	}

	/**
	 * @param array $titles
	 */
	private function extractHistoryPageRevisions( array $titles ) {
		$pagesMap = $this->buckets->getBucketData( 'pages-map' );
		$historyPageTitles = $this->buckets->getBucketData( 'attic-pages-map' );
		$pageTitles = $this->buckets->getBucketData( 'page-titles' );

		$this->output->writeln( "Extract history revisons of page:" );
 
		foreach ( $titles as $title ) {
			$id = $this->makeIdFromTitle( $title );

			if ( !isset( $pagesMap[$title] ) || empty( $pagesMap[$title] ) ) {
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
		}
	}

	/**
	 * @param array $titles
	 * @param array $media
	 */
	private function extractCurrentMediaRevisions( array $media ) {
		$mediaMap = $this->buckets->getBucketData( 'media-map' );
		
		$this->output->writeln( "Extract current revision of media:" );

		foreach ( $media as $title ) {
			$id = $this->makeIdFromTitle( $title );

			if ( isset( $mediaMap[$title] ) && !empty( $mediaMap[$title] ) ) {
				$filepath = $mediaMap[$title][0];
				if ( !file_exists( $filepath ) ) {
					continue;
				}
				$this->extractCurrentMediaRevision( $title, $filepath, $id );
			} else {
				continue;
			}
		}
	}

	/**
	 * @param array $media
	 */
	private function extractHistoryMediaRevisions( array $media ) {
		$mediaMap = $this->buckets->getBucketData( 'media-map' );
		$historyMediaTitles = $this->buckets->getBucketData( 'attic-media-map' );
		
		$this->output->writeln( "Extract history revision of media:" );

		foreach ( $media as $title ) {
			$id = $this->makeIdFromTitle( $title );

			if ( !isset( $mediaMap[$title] ) || empty( $mediaMap[$title] ) ) {
				continue;
			}

			if ( isset( $historyMediaTitles[$title] ) && !empty( $historyMediaTitles[$title] ) ) {
				foreach ( $historyMediaTitles[$title] as $filepath ) {
					if ( !file_exists( $filepath ) ) {
						continue;
					}
					$this->extractHistoryMediaRevision( $title, $filepath, $id );
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
		$this->output->writeln( "\t - Extract current revision of title: $title" );
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
		$this->output->writeln( "\t - $title (" . $this->getHumanReadableTimestamp( $timestamp ) . ")" );
	}

	/**
	 * See: https://www.dokuwiki.org/tips:recreate_wiki_change_log
	 * @param string $titles
	 */
	private function extractPageChanges( array $titles ) {
		$changesMap = $this->buckets->getBucketData( 'page-changes-map' );

		foreach ( $titles as $title ) {
			if ( !isset( $changesMap[$title] ) || empty( $changesMap[$title] ) ) {
				continue;
			}

			$this->output->writeln( "Extract page changes of $title" );

			$filepath = $changesMap[$title][0];
			if ( !file_exists( $filepath ) ) {
				continue;
			}

			$id = $this->makeIdFromTitle( $title );

			$content = file_get_contents( $filepath );
			$lines = explode( "\n", $content );

			$changes = [];
			foreach ( $lines as $line ) {
				$matches = [];
				preg_match( '#(\d+)\s(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})\s(.*?)\s(.*?)\s(.*?)\s(.*?)\s#', $line, $matches );

				if ( empty( $matches ) ) {
					continue;
				}

				$object = [];
				$object['timestamp'] = $matches[1];
				$object['ip'] = $matches[2];
				$object['flag'] = $matches[3];
				$object['title'] = $title;
				$object['user'] = $matches[5];
				$object['comment'] = $matches[6];

				$changes[] = $object;
			}
			
			$this->workspace->saveData( $id, $changes, $path = 'content/history/changes' );
		}
	}

	/**
	 * See: https://www.dokuwiki.org/devel:metadata
	 * @param string $titles
	 */
	private function extractPageMeta( array $titles ) {
		$metaMap = $this->buckets->getBucketData( 'page-meta-map' );

		$titleBuilder = new FilenameBuilder();

		foreach ( $titles as $title ) {
			if ( !isset( $metaMap[$title] ) || empty( $metaMap[$title] ) ) {
				continue;
			}

			$this->output->writeln( "Extract page meta of $title" );

			$filepath = $metaMap[$title][0];
			if ( !file_exists( $filepath ) ) {
				continue;
			}

			$id = $this->makeIdFromTitle( $title );

			$content = file_get_contents( $filepath );
			$meta = unserialize( $content, ['allowed_classes' => false] );

			if ( isset( $meta['current']['relation']['media'] ) ) {
				$media = $meta['current']['relation']['media'];

				foreach ( $media as $name => $value ) {
					$paths = explode( ':', $name );
					$fileTitle = $titleBuilder->build( $paths );

					$this->buckets->addData( 'media-name-title-map', $name, $fileTitle, true, true );
				}
			}

			// TODO: Run extract meta bevore extract media and extract only medai linked in $meta['current']['relation']['media']

			$this->workspace->saveData( $id, $meta, $path = 'content/meta' );
		}

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
		$this->output->writeln( "\t - $title" );
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
		$this->output->writeln( "\t - $title (" . $this->getHumanReadableTimestamp( $timestamp ) . ")" );
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