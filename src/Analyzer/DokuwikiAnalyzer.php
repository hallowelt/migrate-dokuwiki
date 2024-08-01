<?php

namespace HalloWelt\MigrateDokuwiki\Analyzer;

use HalloWelt\MediaWiki\Lib\Migration\AnalyzerBase;
use HalloWelt\MediaWiki\Lib\Migration\DataBuckets;
use HalloWelt\MediaWiki\Lib\Migration\IAnalyzer;
use HalloWelt\MediaWiki\Lib\Migration\IOutputAwareInterface;
use HalloWelt\MediaWiki\Lib\Migration\Workspace;
use HalloWelt\MigrateDokuwiki\Utility\FilenameBuilder;
use HalloWelt\MigrateDokuwiki\ISourcePathAwareInterface;
use HalloWelt\MigrateDokuwiki\Utility\TitleBuilder;
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
	private $customBuckets = null;

	/** @var LoggerInterface */
	private $logger = null;

	/** @var Input */
	private $input = null;

	/** @var Output */
	private $output = null;

	/** @var bool */
	private $extNsFileRepoCompat = false;

	/** @var array */
	private $advancedConfig = [];

	/** @var bool */
	private $hasAdvancedConfig = false;

	/** @var string */
	private $src = '';

	/** @var TitleBuilder */
	private $titleBuilder = null;

	/** @var FilenameBuilder */
	private $filenameBuilder = null;

	/**
	 *
	 * @param array $config
	 * @param Workspace $workspace
	 * @param DataBuckets $buckets
	 */
	public function __construct( $config, Workspace $workspace, DataBuckets $buckets ) {
		parent::__construct( $config, $workspace, $buckets );
		$this->customBuckets = new DataBuckets( [
			'namespaces-map',
			'pages-map',
			'page-titles',
			'media-map',
			'media-titles',
			'page-meta-map',
			'page-changes-map',
			'attic-namespaces-map',
			'attic-pages-map',
			'attic-media-map',
		] );
		$this->logger = new NullLogger();
		$this->titleBuilder = new TitleBuilder();
		$this->filenameBuilder = new FilenameBuilder();

		if ( isset( $this->config['config'] ) ) {
			$this->advancedConfig = $this->config['config'];
			$this->hasAdvancedConfig = true;
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
		$this->customBuckets->loadFromWorkspace( $this->workspace );
		$result = parent::analyze( $file );

		$this->customBuckets->saveToWorkspace( $this->workspace );
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
		if ( $paths[0] !== 'data' ) {
			// Sub directory in input folder
			unset( $paths[0] );
			$paths = array_values( $paths );
		}

		if ( $paths[1] === 'pages'
			|| ( $paths[1] === 'git' && $paths[2] === 'pages' )
		) {
			if ( $file->getExtension() !== 'txt' ) {
				return true;
			}
			$this->makeLatestRevisionPageMap( $file, $paths );
		} elseif ( $paths[1] === 'attic' ) {
			if ( $file->getExtension() !== 'txt' ) {
				return true;
			}
			$this->makeHistoryRevisionPageMap( $file, $paths );
		} elseif ( $paths[1] === 'media'
			|| ( $paths[1] === 'git' && $paths[2] === 'media' )
		) {
			$this->makeLatestRevisionMediaMap( $file, $paths );
		} elseif ( $paths[1] === 'media_attic' ) {
			$this->makeHistoryRevisionMediaMap( $file, $paths );
		} elseif ( $paths[1] === 'meta' ) {
			$this->makeLatestRevisionMetaMap( $file, $paths );
		}

		return true;
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
		if ( count( $paths ) === 1 ) {
			array_unshift( $paths, 'GENERAL' );
		}

		$namespace = $paths[0];
		$this->customBuckets->addData( 'namespaces-map', 'namespaces', $namespace, true, true );

		$title = $this->makeTitle( $paths );
		$this->output->writeln( "Add title:  $title" );
		$this->customBuckets->addData( 'page-titles', 'pages_titles', $title, true, false );
		$this->customBuckets->addData( 'pages-map', $title, $file->getPathname(), true, false );
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
		if ( count( $paths ) === 1 ) {
			array_unshift( $paths, 'GENERAL' );
		}

		$namespace = $paths[0];
		$this->customBuckets->addData( 'attic-namespaces-map', 'namespaces', $namespace, true, true );

		$title = $this->makeTitle( $paths, true );
		$this->output->writeln( "Add history version of:  $title" );
		$this->customBuckets->addData( 'attic-pages-map', $title, $file->getPathname(), true, false );
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
		if ( count( $paths ) === 1 ) {
			array_unshift( $paths, 'GENERAL' );
		}

		$filename = $this->makeFilename( $paths );
		$this->output->writeln( "Add media: $filename" );
		$this->customBuckets->addData( 'media-titles', 'media_titles', $filename, true, false );
		$this->customBuckets->addData( 'media-map', $filename, $file->getPathname(), true, true );
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
		if ( count( $paths ) === 1 ) {
			array_unshift( $paths, 'GENERAL' );
		}

		$filename = $this->makeFilename( $paths, true );
		$this->output->writeln( "Add history version of media: $filename" );
		$this->customBuckets->addData( 'attic-media-map', $filename, $file->getPathname(), true, true );
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
		if ( count( $paths ) === 1 ) {
			array_unshift( $paths, 'GENERAL' );
		}

		$title = $this->makeTitle( $paths );
		$this->output->writeln( "Add meta for: $title" );
		if ( $file->getExtension() === 'meta' ) {
			$this->customBuckets->addData( 'page-meta-map', $title, $file->getPathname(), true, true );
		} else {
			$this->customBuckets->addData( 'page-changes-map', $title, $file->getPathname(), true, true );
		}
	}

	/**
	 * @param array $paths
	 * @param bool $history
	 * @return string
	 */
	private function makeTitle( array $paths, bool $history = false ): string {
		return $this->titleBuilder->build( $paths, $history );
	}

	/**
	 * @param array $paths
	 * @param bool $history
	 * @param bool $nsFileRepoCompat
	 * @return string
	 */
	private function makeFilename( array $paths, $history = false, $nsFileRepoCompat = false ): string {
		return $this->filenameBuilder->build( $paths, $history, $nsFileRepoCompat );
	}
}
