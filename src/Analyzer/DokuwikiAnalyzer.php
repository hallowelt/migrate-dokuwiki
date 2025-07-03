<?php

namespace HalloWelt\MigrateDokuwiki\Analyzer;

use HalloWelt\MediaWiki\Lib\Migration\AnalyzerBase;
use HalloWelt\MediaWiki\Lib\Migration\DataBuckets;
use HalloWelt\MediaWiki\Lib\Migration\IAnalyzer;
use HalloWelt\MediaWiki\Lib\Migration\IOutputAwareInterface;
use HalloWelt\MediaWiki\Lib\Migration\Workspace;
use HalloWelt\MigrateDokuwiki\ISourcePathAwareInterface;
use HalloWelt\MigrateDokuwiki\Utility\FileKeyBuilder;
use HalloWelt\MigrateDokuwiki\Utility\TitleKeyBuilder;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SplFileInfo;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Output\Output;

class DokuwikiAnalyzer
	extends AnalyzerBase
	implements IAnalyzer, ISourcePathAwareInterface, LoggerAwareInterface, IOutputAwareInterface
{

	/** @var DataBuckets */
	private $dataBuckets = null;

	/** @var LoggerInterface */
	private $logger = null;

	/** @var Input */
	private $input = null;

	/** @var Output */
	private $output = null;

	/** @var array */
	private $advancedConfig = [];

	/** @var string */
	private $src = '';

	/** @var TitleKeyBuilder */
	private $titleKeyBuilder = null;

	/** @var FileKeyBuilder */
	private $fileKeyBuilder = null;

	/**
	 *
	 * @param array $config
	 * @param Workspace $workspace
	 * @param DataBuckets $buckets
	 */
	public function __construct( $config, Workspace $workspace, DataBuckets $buckets ) {
		parent::__construct( $config, $workspace, $buckets );
		$this->dataBuckets = new DataBuckets( [
			'namespace-ids',
			'pages-map',
			'media-map',
			'page-meta-map',
			'page-changes-map',
			'attic-pages-map',
			'attic-media-map',
		] );
		$this->logger = new NullLogger();
		$this->titleKeyBuilder = new TitleKeyBuilder();
		$this->fileKeyBuilder = new FileKeyBuilder();

		if ( isset( $this->config['config'] ) ) {
			$this->advancedConfig = $this->config['config'];
		}
	}

	/**
	 *
	 * @param array $config
	 * @param Workspace $workspace
	 * @param DataBuckets $buckets
	 * @return DokuwikiAnalyzer
	 */
	public static function factory( $config, Workspace $workspace, DataBuckets $buckets ): DokuwikiAnalyzer {
		return new static( $config, $workspace, $buckets );
	}

	/**
	 * @param LoggerInterface $logger
	 */
	public function setLogger( LoggerInterface $logger ) {
		$this->logger = $logger;
	}

	/**
	 * @param Input $input
	 */
	public function setInput( Input $input ) {
		$this->input = $input;
	}

	/**
	 * @param Output $output
	 */
	public function setOutput( Output $output ) {
		$this->output = $output;
	}

	/**
	 * @param string $path
	 * @return void
	 */
	public function setSourcePath( $path ) {
		$this->src = $path;
	}

	/**
	 * @param SplFileInfo $file
	 * @return bool
	 */
	public function analyze( SplFileInfo $file ): bool {
		$this->dataBuckets->loadFromWorkspace( $this->workspace );
		$result = parent::analyze( $file );

		$this->dataBuckets->saveToWorkspace( $this->workspace );
		return $result;
	}

	/**
	 *
	 * @param SplFileInfo $file
	 * @return bool
	 */
	protected function doAnalyze( SplFileInfo $file ): bool {
		if ( substr( $file->getPathname(), 0, strlen( $this->src ) ) !== $this->src ) {
			return true;
		}

		$filepath = str_replace( $this->src, '', $file->getPathname() );
		$paths = explode( '/', trim( $filepath, '/' ) );

		if ( $paths[0] === 'data' ) {
			unset( $paths[0] );
			$paths = array_values( $paths );
		}

		if ( $paths[0] === 'git' ) {
			unset( $paths[0] );
			$paths = array_values( $paths );
		}

		if ( empty( $paths ) ) {
			return true;
		}

		// Sub directory in input folder
		while ( !$this->isProperSource( $paths[0] ) ) {
			if ( count( $paths ) > 2 ) {
				unset( $paths[0] );
				$paths = array_values( $paths );
			} else {
				return true;
			}
		}

		if ( $paths[0] === 'pages' ) {
			if ( $file->getExtension() !== 'txt' ) {
				return true;
			}
			$this->makeLatestRevisionPageMap( $file, $paths );
		} elseif ( $paths[0] === 'attic' ) {
			if ( $file->getExtension() !== 'txt' ) {
				return true;
			}
			$this->makeHistoryRevisionPageMap( $file, $paths );
		} elseif ( $paths[0] === 'media' ) {
			$this->makeLatestRevisionMediaMap( $file, $paths );
		} elseif ( $paths[0] === 'media_attic' ) {
			// $this->makeHistoryRevisionMediaMap( $file, $paths );
		} elseif ( $paths[0] === 'meta' ) {
			$this->makeLatestRevisionMetaMap( $file, $paths );
		}

		return true;
	}

	/**
	 * @param string $path
	 * @return bool
	 */
	private function isProperSource( string $path ): bool {
		$validPaths = [
			'pages', 'attic', 'meta',
			'media', 'media_attic', 'media_meta'
		];
		if ( in_array( $path, $validPaths ) ) {
			return true;
		}

		return false;
	}

	/**
	 * @param SplFileInfo $file
	 * @param array $paths
	 */
	private function makeLatestRevisionPageMap( SplFileInfo $file, array $paths ) {
		if ( $paths[0] === 'pages' ) {
			unset( $paths[0] );
			$paths = array_values( $paths );
		}

		if ( count( $paths ) < 2 ) {
			// .txt is a direct child of pages directory.
			// It has to result in a page in NS_MAIN or it is the main page of a namespace.
			$this->namespaceMainpage( $paths, $file );
		}

		$key = $this->makeTitleKey( $paths );
		if ( count( $paths ) > 1 ) {
			// Creating namespace id for bucket.
			$namespaceId = $this->makeTitleKey( [ $paths[0] ] );
			$this->dataBuckets->addData( 'namespace-ids', 'namespace-ids', $namespaceId, true, true );
		}
		$this->output->writeln( "Add latest page revision: {$file->getRealPath()}" );
		$this->dataBuckets->addData( 'pages-map', $key, $file->getPathname(), true, false );
	}

	/**
	 * @param SplFileInfo $file
	 * @param array $paths
	 */
	private function makeHistoryRevisionPageMap( SplFileInfo $file, array $paths ) {
		if ( $paths[0] === 'attic' ) {
			unset( $paths[0] );
			$paths = array_values( $paths );
		}

		if ( count( $paths ) < 2 ) {
			// .txt is a direct child of pages directory.
			// It has to result in a page in NS_MAIN or it is the main page of a namespace.
			$this->namespaceMainpage( $paths, $file, true );
		}

		$lastKey = array_key_last( $paths );
		$parts = explode( '.', $paths[$lastKey] );
		$extension = array_pop( $parts );
		$timestamp = array_pop( $parts );
		$parts[] = $extension;
		$paths[$lastKey] = implode( '.', $parts );

		$key = $this->makeTitleKey( $paths );
		$this->output->writeln( "Add attic page revision: {$file->getRealPath()}" );
		$this->dataBuckets->addData( 'attic-pages-map', $key, $file->getPathname(), true, false );
	}

	/**
	 * @param SplFileInfo $file
	 * @param array $paths
	 */
	private function makeLatestRevisionMediaMap( SplFileInfo $file, array $paths ) {
		if ( $paths[0] === 'media' ) {
			unset( $paths[0] );
			$paths = array_values( $paths );
		}

		$key = $this->makeFileKey( $paths );
		$this->output->writeln( "Add media: {$file->getRealPath()}" );
		$this->dataBuckets->addData( 'media-map', $key, $file->getPathname(), true, true );
	}

	/**
	 * @param SplFileInfo $file
	 * @param array $paths
	 */
	private function makeHistoryRevisionMediaMap( SplFileInfo $file, array $paths ) {
		if ( $paths[0] === 'media_attic' ) {
			unset( $paths[0] );
			$paths = array_values( $paths );
		}

		$key = $this->makeFileKey( $paths );
		$this->output->writeln( "Add attic media: {$file->getRealPath()}" );
		$this->dataBuckets->addData( 'attic-media-map', $key, $file->getPathname(), true, true );
	}

	/**
	 * @param SplFileInfo $file
	 * @param array $paths
	 */
	private function makeLatestRevisionMetaMap( SplFileInfo $file, array $paths ) {
		// There are different files like .changes with page mod history. we just want .meta
		if ( $file->getExtension() !== 'meta' && $file->getExtension() !== 'changes' ) {
			return;
		}

		if ( $paths[0] === 'meta' ) {
			unset( $paths[0] );
			$paths = array_values( $paths );
		}

		$key = $this->makeTitleKey( $paths );
		if ( $file->getExtension() === 'meta' ) {
			$this->output->writeln( "Add meta: {$file->getRealPath()}" );
			$this->dataBuckets->addData( 'page-meta-map', $key, $file->getPathname(), true, true );
		} else {
			$this->output->writeln( "Add changes: {$file->getRealPath()}" );
			$this->dataBuckets->addData( 'page-changes-map', $key, $file->getPathname(), true, true );
		}
	}

	/**
	 * @param array &$paths
	 * @param SplFileInfo $file
	 * @param bool $history
	 * @return void
	 */
	private function namespaceMainpage( array &$paths, SplFileInfo $file, $history = false ): void {
		// Removing file extension
		$dir = substr( $file->getRealPath(), 0, strrpos( $file->getRealPath(), '.' ) );
		if ( $history ) {
			// Removing timestamp
			$dir = substr( $dir, 0, strrpos( $dir, '.' ) );
		}
		if ( is_dir( $dir ) ) {
			$namespace = trim( $paths[0] );
			$namespace = trim( $namespace, '.txt' );
			$namespace = trim( $namespace, " _\t" );
			$namespace = str_replace( [ '-', ' ' ], '_', $namespace );

			$paths[1] = $paths[0];
			$paths[0] = $namespace;
		}
	}

	/**
	 * @param array $paths
	 * @return string
	 */
	private function makeTitleKey( array $paths ): string {
		return $this->titleKeyBuilder->build( $paths );
	}

	/**
	 * @param array $paths
	 * @return string
	 */
	private function makeFileKey( array $paths ): string {
		return $this->fileKeyBuilder->build( $paths );
	}
}
