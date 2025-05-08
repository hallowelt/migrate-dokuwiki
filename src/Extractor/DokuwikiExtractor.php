<?php

namespace HalloWelt\MigrateDokuwiki\Extractor;

use HalloWelt\MediaWiki\Lib\Migration\DataBuckets;
use HalloWelt\MediaWiki\Lib\Migration\IOutputAwareInterface;
use HalloWelt\MediaWiki\Lib\Migration\Workspace;
use HalloWelt\MigrateDokuwiki\IExtractor;
use HalloWelt\MigrateDokuwiki\Utility\FileTitleBuilder;
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
			// From this step
			'namespaces-map',
			'pages-map',
			'page-titles',
			'page-key-to-title-map',
			'page-changes-map',
			'page-meta-map',
			'media-map',
			'media-titles',
			'page-meta-map',
			'page-changes-map',
			'attic-namespaces-map',
			'attic-pages-map',
			'attic-media-map',
			'attic-meta-map',
			'page-id-to-title-map',
			'page-id-to-attic-page-id',
			'media-key-to-title-map',
			'media-title-to-media-path'
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
		$pageTitles = $this->dataBuckets->getBucketData( 'page-titles' );
		$mediaTitles = $this->dataBuckets->getBucketData( 'media-titles' );

		$titles = [];
		if ( isset( $pageTitles['pages_titles'] ) ) {
			$titles = $pageTitles['pages_titles'];
		}

		$media = [];
		if ( isset( $mediaTitles['media_titles'] ) ) {
			$media = $mediaTitles['media_titles'];
		}

		$this->extractCurrentPageRevisions( $titles );
		$this->extractHistoryPageRevisions( $titles );
		$this->extractCurrentMediaRevisions( $media );
		// $this->extractHistoryMediaRevisions( $media );
		$this->extractPageChanges( $titles );
		$this->extractPageMeta( $titles );

		$this->dataBuckets->saveToWorkspace( $this->workspace );
		return true;
	}

	/**
	 * @param array $titles
	 */
	private function extractCurrentPageRevisions( array $titles ) {
		$pagesMap = $this->dataBuckets->getBucketData( 'pages-map' );

		$this->output->writeln( "Extract current revisons of page:" );

		foreach ( $titles as $title ) {
			$id = $this->getIdFromPageTitle( $title );

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
		$pagesMap = $this->dataBuckets->getBucketData( 'pages-map' );
		$historyPageTitles = $this->dataBuckets->getBucketData( 'attic-pages-map' );
		$pageTitles = $this->dataBuckets->getBucketData( 'page-titles' );

		$this->output->writeln( "Extract history revisons of page:" );

		foreach ( $titles as $title ) {
			$id = $this->getIdFromPageTitle( $title );

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
	 * @param array $media
	 */
	private function extractCurrentMediaRevisions( array $media ) {
		$mediaMap = $this->dataBuckets->getBucketData( 'media-map' );

		$this->output->writeln( "Extract current revision of media:" );

		foreach ( $media as $title ) {
			$id = $this->getIdFromMediaTitle( $title );

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
		$mediaMap = $this->dataBuckets->getBucketData( 'media-map' );
		$historyMediaTitles = $this->dataBuckets->getBucketData( 'attic-media-map' );

		$this->output->writeln( "Extract history revision of media:" );

		foreach ( $media as $title ) {
			$id = $this->getIdFromMediaTitle( $title );

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
		$id = str_replace( ':', '_', $id );
		$targetFileName = $this->workspace->saveRawContent( $id, $content );
		$this->dataBuckets->addData( 'page-id-to-title-map', $id, $title, true, true );
		$this->dataBuckets->addData( 'page-id-to-page-contents', $id, $targetFileName, true, true );
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
		$filename = $this->makeFilenameForHistoryVersion( $filenameParts, $id );
		$content = file_get_contents( $filepath );
		$targetFileName = $this->workspace
			->saveRawContent( $filename, $content, 'content/raw/' );
		$this->dataBuckets->addData( 'page-id-to-attic-page-id', $id, $filename, true, true );
		$this->dataBuckets->addData( 'page-id-to-attic-page-contents', $id, $targetFileName, true, true );
		$this->output->writeln( "\t - $title (" . $this->getHumanReadableTimestamp( $timestamp ) . ")" );
	}

	/**
	 * See: https://www.dokuwiki.org/tips:recreate_wiki_change_log
	 * @param array $titles
	 */
	private function extractPageChanges( array $titles ) {
		$changesMap = $this->dataBuckets->getBucketData( 'page-changes-map' );

		foreach ( $titles as $title ) {
			if ( !isset( $changesMap[$title] ) || empty( $changesMap[$title] ) ) {
				continue;
			}

			$this->output->writeln( "Extract page changes of $title" );

			$filepath = $changesMap[$title][0];
			if ( !file_exists( $filepath ) ) {
				continue;
			}

			$id = $this->getIdFromPageTitle( $title );
			$id = str_replace( ':', '_', $id );

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

			$this->workspace->saveData( $id, $changes, $path = 'content/history/changes' );
		}
	}

	/**
	 * See: https://www.dokuwiki.org/devel:metadata
	 * @param array $titles
	 */
	private function extractPageMeta( array $titles ) {
		$metaMap = $this->dataBuckets->getBucketData( 'page-meta-map' );

		$titleBuilder = new FileTitleBuilder();

		foreach ( $titles as $title ) {
			if ( !isset( $metaMap[$title] ) || empty( $metaMap[$title] ) ) {
				continue;
			}

			$this->output->writeln( "Extract page meta of $title" );

			$filepath = $metaMap[$title][0];
			if ( !file_exists( $filepath ) ) {
				continue;
			}

			$id = $this->getIdFromPageTitle( $title );
			$id = str_replace( ':', '_', $id );

			$content = file_get_contents( $filepath );
			$meta = unserialize( $content, [ 'allowed_classes' => false ] );

			if ( isset( $meta['current']['relation']['media'] ) ) {
				$media = $meta['current']['relation']['media'];

				foreach ( $media as $name => $value ) {
					$paths = explode( ':', $name );
					$fileTitle = $titleBuilder->build( $paths );

					$this->dataBuckets->addData( 'used-key-name-title-map', $name, $fileTitle, true, true );
				}
			}

			// TODO: Run extract meta bevore extract media
			// and extract only media linked in $meta['current']['relation']['media']

			$this->workspace->saveData( $id, $meta, 'content/meta' );
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
		$this->dataBuckets->addData( 'media-title-to-media-path', $title, $targetFileName, true, true );
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
		$filename = $this->makeFilenameForHistoryVersion( $filenameParts, $id );
		$content = file_get_contents( $filepath );
		$targetFileName = $this->workspace->saveUploadFile( $filename, $content, 'content/history/images/' . $id );
		$this->dataBuckets->addData( 'media-id-to-attic-media-contents', $id, $targetFileName, true, true );
		$this->output->writeln( "\t - $title (" . $this->getHumanReadableTimestamp( $timestamp ) . ")" );
	}

	/**
	 * @return string
	 */
	private function getIdFromPageTitle( string $title ) {
		$key = $this->getId( $title, 'page-key-to-title-map' );
		return $key;
	}

	/**
	 * @return string
	 */
	private function getIdFromMediaTitle( string $title ) {
		$key = $this->getId( $title, 'media-key-to-title-map' );
		return $key;
	}

	/**
	 * @return string
	 */
	private function getId( string $title, string $bucket ) {
		$key = md5( $title );
		$keyMap = $this->dataBuckets->getBucketData( $bucket );
		if ( in_array( $title, $keyMap ) ) {
			$mapKey = array_search( $title, $keyMap );
			if ( is_string( $mapKey ) ) {
				$key = $mapKey;
			}
		}
		return $key;
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

	/**
	 * @param string $timestamp
	 * @return string
	 */
	private function getHumanReadableTimestamp( string $timestamp ): string {
		return date( 'Y-m-d H:i:s', $timestamp );
	}
}
