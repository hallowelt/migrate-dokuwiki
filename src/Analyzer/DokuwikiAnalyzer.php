<?php

namespace HalloWelt\MigrateDokuwiki\Analyzer;

use HalloWelt\MediaWiki\Lib\Migration\AnalyzerBase;
use HalloWelt\MediaWiki\Lib\Migration\DataBuckets;
use HalloWelt\MediaWiki\Lib\Migration\IAnalyzer;
use HalloWelt\MediaWiki\Lib\Migration\IOutputAwareInterface;
use HalloWelt\MediaWiki\Lib\Migration\Workspace;
use HalloWelt\MigrateDokuwiki\ISourcePathAwareInterface;
use HalloWelt\MigrateDokuwiki\Utility\FileKeyBuilder;
use HalloWelt\MigrateDokuwiki\Utility\FileTitleBuilder;
use HalloWelt\MigrateDokuwiki\Utility\TitleBuilder;
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

	/** @var TitleBuilder */
	private $titleBuilder = null;

	/** @var TitleKeyBuilder */
	private $titleKeyBuilder = null;

	/** @var FileTitleBuilder */
	private $fileTitleBuilder = null;

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
			'namespaces-map',
			'pages-map',
			'page-titles',
			'page-key-to-title-map',
			'media-map',
			'media-titles',
			'media-key-to-title-map',
			'page-meta-map',
			'page-changes-map',
			'attic-namespaces-map',
			'attic-pages-map',
			'attic-media-map',
		] );
		$this->logger = new NullLogger();
		$this->titleBuilder = new TitleBuilder();
		$this->titleKeyBuilder = new TitleKeyBuilder();
		$this->fileTitleBuilder = new FileTitleBuilder();
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

		// Sub directory in input folder
		while ( !$this->isProperSource( $paths ) ) {
			if ( count( $paths ) === 2 ) {
				break;
			}

			unset( $paths[0] );
			$paths = array_values( $paths );
		}

		if ( $paths[0] === 'pages'
			|| ( $paths[0] === 'git' && $paths[1] === 'pages' )
		) {
			if ( $file->getExtension() !== 'txt' ) {
				return true;
			}
			$this->makeLatestRevisionPageMap( $file, $paths );
		} elseif ( $paths[0] === 'attic' ) {
			if ( $file->getExtension() !== 'txt' ) {
				return true;
			}
			$this->makeHistoryRevisionPageMap( $file, $paths );
		} elseif ( $paths[0] === 'media'
			|| ( $paths[0] === 'git' && $paths[1] === 'media' )
		) {
			$this->makeLatestRevisionMediaMap( $file, $paths );
		} elseif ( $paths[0] === 'media_attic' ) {
			// $this->makeHistoryRevisionMediaMap( $file, $paths );
		} elseif ( $paths[0] === 'meta' ) {
			$this->makeLatestRevisionMetaMap( $file, $paths );
		}

		return true;
	}

	/**
	 * @param array $paths
	 * @return bool
	 */
	private function isProperSource( array $paths ): bool {
		if ( $paths[0] === 'pages' ) {
			return true;
		}
		if ( $paths[0] === 'media' ) {
			return true;
		}
		if ( $paths[0] === 'attic' ) {
			return true;
		}
		if ( $paths[0] === 'media_attic' ) {
			return true;
		}
		if ( $paths[0] === 'media_meta' ) {
			return true;
		}
		if ( $paths[0] === 'meta' ) {
			return true;
		}
		if ( $paths[0] === 'git' && $paths[1] === 'pages' ) {
			return true;
		}
		if ( $paths[0] === 'git' && $paths[1] === 'media' ) {
			return true;
		}
		return false;
	}

	/**
	 * @param SplFileInfo $file
	 * @param array $paths
	 */
	private function makeLatestRevisionPageMap( SplFileInfo $file, array $paths ) {
		if ( $paths[0] === 'data' ) {
			unset( $paths[0] );
			$paths = array_values( $paths );
		}
		if ( $paths[0] === 'git' ) {
			unset( $paths[0] );
			$paths = array_values( $paths );
		}
		if ( $paths[0] === 'pages' ) {
			unset( $paths[0] );
			$paths = array_values( $paths );
		}

		if ( count( $paths ) < 2 ) {
			// .txt is a direct child of pages directory. It has to result in a page in NS_MAIN
			$namespace = 'NS_MAIN';
		} else {
			$namespace = trim( $paths[0] );
			$namespace = trim( $namespace, " _\t" );
			$namespace = ucfirst( $namespace );
			$namespace = str_replace( [ '-', ' ' ], '_', $namespace );
		}
		$this->dataBuckets->addData( 'namespaces-map', 'namespaces', $namespace, true, true );

		$key = $this->makeTitleKey( $paths );
		$doubleKey = $this->makeTitleDoubleKey( $paths );
		$title = $this->makeTitle( $paths );
		$this->output->writeln( "Add title:  $title" );
		$this->dataBuckets->addData( 'page-key-to-title-map', $key, $title, false, true );
		$this->dataBuckets->addData( 'page-key-to-title-map', $doubleKey, $title, false, true );
		$this->dataBuckets->addData( 'page-titles', 'pages_titles', $title, true, false );
		$this->dataBuckets->addData( 'pages-map', $title, $file->getPathname(), true, false );
	}

	/**
	 * @param SplFileInfo $file
	 * @param array $paths
	 */
	private function makeHistoryRevisionPageMap( SplFileInfo $file, array $paths ) {
		if ( $paths[0] === 'data' ) {
			unset( $paths[0] );
			$paths = array_values( $paths );
		}
		if ( $paths[0] === 'attic' ) {
			unset( $paths[0] );
			$paths = array_values( $paths );
		}

		$namespace = trim( $paths[0] );
		$namespace = trim( $namespace, " _\t" );
		$namespace = ucfirst( $namespace );
		$namespace = str_replace( [ '-', ' ' ], '_', $namespace );
		$this->dataBuckets->addData( 'attic-namespaces-map', 'namespaces', $namespace, true, true );

		$title = $this->makeTitle( $paths, true );
		$this->output->writeln( "Add history version of:  $title" );
		$this->dataBuckets->addData( 'attic-pages-map', $title, $file->getPathname(), true, false );
	}

	/**
	 * @param SplFileInfo $file
	 * @param array $paths
	 */
	private function makeLatestRevisionMediaMap( SplFileInfo $file, array $paths ) {
		if ( $paths[0] === 'data' ) {
			unset( $paths[0] );
			$paths = array_values( $paths );
		}
		if ( $paths[0] === 'git' ) {
			unset( $paths[0] );
			$paths = array_values( $paths );
		}
		if ( $paths[0] === 'media' ) {
			unset( $paths[0] );
			$paths = array_values( $paths );
		}

		$key = $this->makeFileKey( $paths );
		$filename = $this->makeFileTitle( $paths );
		$this->output->writeln( "Add media: $filename" );
		$this->dataBuckets->addData( 'media-key-to-title-map', $key, $filename, false, true );
		$this->dataBuckets->addData( 'media-titles', 'media_titles', $filename, true, false );
		$this->dataBuckets->addData( 'media-map', $filename, $file->getPathname(), true, true );
	}

	/**
	 * @param SplFileInfo $file
	 * @param array $paths
	 */
	private function makeHistoryRevisionMediaMap( SplFileInfo $file, array $paths ) {
		if ( $paths[0] === 'data' ) {
			unset( $paths[0] );
			$paths = array_values( $paths );
		}
		if ( $paths[0] === 'media_attic' ) {
			unset( $paths[0] );
			$paths = array_values( $paths );
		}

		$filename = $this->makeFileTitle( $paths, true );
		$this->output->writeln( "Add history version of media: $filename" );
		$this->dataBuckets->addData( 'attic-media-map', $filename, $file->getPathname(), true, true );
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

		if ( $paths[0] === 'data' ) {
			unset( $paths[0] );
			$paths = array_values( $paths );
		}
		if ( $paths[0] === 'meta' ) {
			unset( $paths[0] );
			$paths = array_values( $paths );
		}

		$title = $this->makeTitle( $paths );
		if ( $file->getExtension() === 'meta' ) {
			$this->output->writeln( "Add meta for: $title" );
			$this->dataBuckets->addData( 'page-meta-map', $title, $file->getPathname(), true, true );
		} else {
			$this->output->writeln( "Add changes for: $title" );
			$this->dataBuckets->addData( 'page-changes-map', $title, $file->getPathname(), true, true );
		}
	}

	/**
	 * @param array $paths
	 * @param bool $history
	 * @return string
	 */
	private function makeTitle( array $paths, bool $history = false ): string {
		return $this->titleBuilder->build( $paths, $history, $this->config );
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
	private function makeTitleDoubleKey( array $paths ): string {
		return $this->titleKeyBuilder->buildDoubleKey( $paths );
	}

	/**
	 * @param array $paths
	 * @param bool $history
	 * @return string
	 */
	private function makeFileTitle( array $paths, $history = false ): string {
		return $this->fileTitleBuilder->build( $paths, $history, $this->advancedConfig );
	}

	/**
	 * @param array $paths
	 * @return string
	 */
	private function makeFileKey( array $paths ): string {
		return $this->fileKeyBuilder->build( $paths );
	}
}
