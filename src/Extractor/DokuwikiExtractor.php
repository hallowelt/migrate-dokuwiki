<?php

namespace HalloWelt\MigrateDokuwiki\Extractor;

use HalloWelt\MediaWiki\Lib\Migration\DataBuckets;
use HalloWelt\MediaWiki\Lib\Migration\IOutputAwareInterface;
use HalloWelt\MediaWiki\Lib\Migration\Workspace;
use HalloWelt\MigrateDokuwiki\IExtractor;
use HalloWelt\MigrateDokuwiki\Utility\FileTitleBuilder;
use HalloWelt\MigrateDokuwiki\Utility\TitleBuilder;
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
	protected $dataBuckets = null;

	/** @var TitleBuilder */
	private $titleBuilder;

	/** @var FileTitleBuilder */
	private $fileTitleBuilder;

	/** @var array */
	private $advancedConfig = [];

	/**
	 * @param array $config
	 * @param Workspace $workspace
	 */
	public function __construct( $config, Workspace $workspace ) {
		$this->config = $config;
		$this->workspace = $workspace;
		$this->dataBuckets = new DataBuckets( $this->getBucketKeys() );
		$this->dataBuckets->loadFromWorkspace( $this->workspace );
		if ( isset( $this->config['config'] ) ) {
			$this->advancedConfig = $this->config['config'];
		}

		$this->titleBuilder = new TitleBuilder();
		$this->fileTitleBuilder = new FileTitleBuilder();
	}

	/**
	 * @param array $config
	 * @param Workspace $workspace
	 * @return IExtractor
	 */
	public static function factory( $config, Workspace $workspace ): IExtractor {
		return new static( $config, $workspace );
	}

	/**
	 * @inheritDoc
	 */
	protected function getBucketKeys() {
		return [
			'pages-map',
			'attic-pages-map',
			'page-meta-map',
			'page-changes-map',
			'media-map',
			'attic-media-map',
			// From this step
			'page-id-to-title-map',
			'page-id-to-meta-title-map',
			'media-id-to-title-map',
		];
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
	 * @return bool
	 */
	protected function doExtract(): bool {
		// page meta and page titles
		$pageIdToTitlesMap = $this->extractPageTitles();

		// media meta and media titles
		$mediaIdToTitles = $this->extractMediaTitles();
		// extract content
		$this->extractCurrentPageRevisions( $pageIdToTitlesMap );
		$this->extractHistoryPageRevisions( $pageIdToTitlesMap );
		$this->extractCurrentMediaRevisions( $mediaIdToTitles );
		$this->extractPageChanges( $pageIdToTitlesMap );
		$this->extractPageMeta( $pageIdToTitlesMap );

		$this->dataBuckets->saveToWorkspace( $this->workspace );
		return true;
	}

	/**
	 * @return array
	 */
	private function extractPageTitles(): array {
		$this->output->writeln( "\t - Extract page titles:" );

		$pagesMap = $this->dataBuckets->getBucketData( 'pages-map' );
		$pageMetaMap = $this->dataBuckets->getBucketData( 'page-meta-map' );

		$metaTitles = [];
		foreach ( $pageMetaMap as $id => $paths ) {
			if ( empty( $paths ) ) {
				continue;
			}

			// Some dokuwiki have a xyz,meta in the directory xyz and at the same level like directory xyz
			// In that case we want to handle the second one
			$path = array_pop( $paths );
			if ( file_exists( $path ) ) {
				$metaContent = file_get_contents( $path );
				$meta = unserialize( $metaContent );

				if ( isset( $meta['current']['title'] ) && $meta['current']['title'] !== '' ) {
					$metaTitles[$id] = $meta['current']['title'];
				}
			}
		}

		$pageIdToTitlesMap = [];
		foreach ( $pagesMap as $id => $path ) {
			$paths = [];
			if ( str_contains( $id, ':' ) ) {
				$paths = explode( ':', $id );
			} else {
				$paths = [ $id ];
			}

			for ( $index = 0; $index < count( $paths ); $index++ ) {
				$partialId = implode( ':', array_slice( $paths, 0, $index + 1 ) );

				if ( isset( $metaTitles[$partialId] ) ) {
					if ( isset( $paths[1] ) && $paths[0] === $paths[1] && $index === 0 ) {
							// Namespace and namespace main page
							$paths[0] = $paths[1] = $metaTitles[$partialId];
					} else {
						$paths[$index] = $metaTitles[$partialId];
					}
				}
			}
			$metaTitle = implode( ':', $paths );
			$this->dataBuckets->addData( 'page-id-to-meta-title-map', $id, $metaTitle, false, true );

			$title = $this->titleBuilder->build( $id, $paths, false, $this->advancedConfig );
			$pageIdToTitlesMap[$id] = $title;
			$this->dataBuckets->addData( 'page-id-to-title-map', $id, $title, false, true );
			$doubleId = explode( ':', $id );
			$lastId = array_pop( $doubleId );
			$doubleId[] = $lastId;
			$doubleId[] = $lastId;
			$doubleId = implode( ':', $doubleId );
			$this->dataBuckets->addData( 'page-id-to-title-map', $id, $title, false, true );
			$this->output->writeln( "\t - $id: $title" );
		}

		return $pageIdToTitlesMap;
	}

	/**
	 * @return array
	 */
	private function extractMediaTitles(): array {
		$this->output->writeln( "\t - Extract media titles:" );

		$mediaMap = $this->dataBuckets->getBucketData( 'media-map' );
		$pageIdToTitlesMap = $this->dataBuckets->getBucketData( 'page-id-to-title-map' );

		$mediaIdToTitles = [];
		foreach ( $mediaMap as $id => $path ) {
			$paths = [];
			if ( str_contains( $id, ':' ) ) {
				$paths = explode( ':', $id );
			} else {
				$paths = [ $id ];
			}

			for ( $index = 0; $index < count( $paths ); $index++ ) {
				$partialId = implode( ':', array_slice( $paths, 0, $index + 1 ) );
				if ( isset( $pageIdToTitlesMap[$partialId] ) ) {
					$paths[$index] = $pageIdToTitlesMap[$partialId];
				}
			}

			$title = $this->fileTitleBuilder->build( $paths, false, $this->advancedConfig );
			$mediaIdToTitles[$id] = $title;
			$this->dataBuckets->addData( 'media-id-to-title-map', $id, $title, false, true );
			$doubleId = explode( ':', $id );
			$lastId = array_pop( $doubleId );
			$doubleId[] = $lastId;
			$doubleId[] = $lastId;
			$doubleId = implode( ':', $doubleId );
			$this->dataBuckets->addData( 'media-id-to-title-map', $id, $title, false, true );
			$this->output->writeln( "\t - $id: $title" );
		}

		return $mediaIdToTitles;
	}

	/**
	 * @param array $pageIdToTitlesMap
	 */
	private function extractCurrentPageRevisions( array $pageIdToTitlesMap ) {
		$this->output->writeln( "Extract current revisons of page:" );

		$pagesMap = $this->dataBuckets->getBucketData( 'pages-map' );
		foreach ( $pageIdToTitlesMap as $id => $title ) {
			if ( isset( $pagesMap[$id] ) && !empty( $pagesMap[$id] ) ) {
				$filepath = $pagesMap[$id][0];
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
	 * @param string $title
	 * @param string $filepath
	 * @param string $id
	 */
	private function extractCurrentPageRevision( string $title, string $filepath, string $id ) {
		$content = file_get_contents( $filepath );
		$filename = str_replace( ':', '_', $id );
		$targetFilePath = $this->workspace->saveRawContent( $filename, $content );
		$this->dataBuckets->addData( 'page-id-to-page-contents', $id, $targetFilePath, true, true );
		$this->output->writeln( "\t - Extract current revision of title: $title" );
	}

	/**
	 * @param array $pageIdToTitlesMap
	 */
	private function extractHistoryPageRevisions( array $pageIdToTitlesMap ) {
		$this->output->writeln( "Extract history revisons of pages:" );

		$historyPagesMap = $this->dataBuckets->getBucketData( 'attic-pages-map' );
		foreach ( $pageIdToTitlesMap as $id => $title ) {
			if ( isset( $historyPagesMap[$id] ) && !empty( $historyPagesMap[$id] ) ) {
				foreach ( $historyPagesMap[$id] as $filepath ) {
					if ( !file_exists( $filepath ) ) {
						continue;
					}

					$parts = explode( '.', $filepath );
					$extension = array_pop( $parts );
					$timestamp = array_pop( $parts );
					$this->extractHistoryPageRevision( $title, $filepath, $id, $timestamp );
				}
			}
		}
	}

	/**
	 * @param string $title
	 * @param string $filepath
	 * @param string $id
	 * @param string $timestamp
	 * @return void
	 */
	private function extractHistoryPageRevision( string $title, string $filepath, string $id, string $timestamp ) {
		$filename = str_replace( ':', '_', $id );
		$filename .= ".{$timestamp}";
		$content = file_get_contents( $filepath );
		$targetFileName = $this->workspace->saveRawContent( $filename, $content, 'content/raw' );
		$this->dataBuckets->addData( 'page-id-to-attic-page-contents', $id, $targetFileName, true, true );
		$this->output->writeln( "\t - Version of $id from $timestamp" );
	}

	/**
	 * @param array $mediaTitles
	 */
	private function extractCurrentMediaRevisions( array $mediaTitles ) {
		$this->output->writeln( "Extract current revision of media:" );

		$mediaMap = $this->dataBuckets->getBucketData( 'media-map' );

		foreach ( $mediaTitles as $id => $title ) {
			if ( isset( $mediaMap[$id] ) && !empty( $mediaMap[$id] ) ) {
				$filepath = $mediaMap[$id][0];
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
	 * @param string $title
	 * @param string $filepath
	 * @param string $id
	 */
	private function extractCurrentMediaRevision( string $title, string $filepath, string $id ) {
		$content = file_get_contents( $filepath );
		$targetFileName = $this->workspace->saveUploadFile( $title, $content );
		$this->dataBuckets->addData( 'media-title-to-media-contents', $id, $targetFileName, true, true );
		$this->output->writeln( "\t - $title" );
	}

	/**
	 * See: https://www.dokuwiki.org/tips:recreate_wiki_change_log
	 * @param array $pageIdToTitlesMap
	 */
	private function extractPageChanges( array $pageIdToTitlesMap ) {
		$changesMap = $this->dataBuckets->getBucketData( 'page-changes-map' );

		foreach ( $pageIdToTitlesMap as $id => $title ) {
			if ( !isset( $changesMap[$id] ) || empty( $changesMap[$id] ) ) {
				continue;
			}

			$this->output->writeln( "Extract page changes of $title" );

			$filepath = $changesMap[$id][0];
			if ( !file_exists( $filepath ) ) {
				continue;
			}

			$filename = str_replace( ':', '_', $id );

			$content = file_get_contents( $filepath );
			$lines = explode( "\n", $content );

			$changes = [];
			foreach ( $lines as $line ) {
				$matches = [];
				$regEx = '#(\d+)\s(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})\s(.*?)\s(.*?)\s(.*?)\s(.*?)\s#';
				preg_match( $regEx, $line, $matches );

				if ( empty( $matches ) ) {
					continue;
				}

				$timestamp = $matches[1];

				$object = [];
				$object['timestamp'] = $timestamp;
				$object['ip'] = $matches[2];
				$object['flag'] = $matches[3];
				$object['title'] = $title;
				$object['user'] = $matches[5];
				$object['comment'] = $matches[6];

				$changes[$timestamp] = $object;
			}

			$this->workspace->saveData( $filename, $changes, 'content/history/changes' );
		}
	}

	/**
	 * See: https://www.dokuwiki.org/devel:metadata
	 * @param array $pageIdToTitlesMap
	 */
	private function extractPageMeta( array $pageIdToTitlesMap ) {
		$metaMap = $this->dataBuckets->getBucketData( 'page-meta-map' );

		$titleBuilder = new FileTitleBuilder();

		foreach ( $pageIdToTitlesMap as $id => $title ) {
			if ( !isset( $metaMap[$id] ) || empty( $metaMap[$id] ) ) {
				continue;
			}

			$this->output->writeln( "Extract page meta of $title" );

			$filepath = $metaMap[$id][0];
			if ( !file_exists( $filepath ) ) {
				continue;
			}

			$filename = str_replace( ':', '_', $id );

			$content = file_get_contents( $filepath );
			$meta = unserialize( $content, [ 'allowed_classes' => false ] );

			$this->workspace->saveData( $filename, $meta, 'content/meta' );
		}
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
		$filename = $this->makeFilenameForHistoryVersion( $filenameParts, $id );
		$content = file_get_contents( $filepath );
		$targetFileName = $this->workspace->saveUploadFile( $filename, $content, 'content/history/images/' . $id );
		$this->dataBuckets->addData( 'media-id-to-attic-media-contents', $id, $targetFileName, true, true );
		$this->output->writeln( "\t - $title (" . $this->getHumanReadableTimestamp( $timestamp ) . ")" );
	}

	/**
	 * @param string $filepath
	 * @return array
	 */
	private function getFilenameParts( string $filepath ): array {
		$file = new SplFileInfo( $filepath );
		$filename = $file->getFilename();
		return explode( '.', $filename );
	}

	/**
	 * @param array $filenameParts
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
	 * @param string $timestamp
	 * @return string
	 */
	private function getHumanReadableTimestamp( string $timestamp ): string {
		return date( 'Y-m-d H:i:s', $timestamp );
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
	 * @param string $id
	 * @return string
	 */
	private function makeFilenameForHistoryVersion( array $paths, string $id ): string {
		$timestamp = $this->getTimestampOfHistoryVersion( $paths );
		$fileExtension = array_pop( $paths );
		$id = str_replace( ':', '_', $id );
		return "$id.$timestamp";
	}

}
