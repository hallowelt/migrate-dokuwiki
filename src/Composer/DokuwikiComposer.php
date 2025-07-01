<?php

namespace HalloWelt\MigrateDokuwiki\Composer;

use HalloWelt\MediaWiki\Lib\MediaWikiXML\Builder;
use HalloWelt\MediaWiki\Lib\Migration\ComposerBase;
use HalloWelt\MediaWiki\Lib\Migration\DataBuckets;
use HalloWelt\MediaWiki\Lib\Migration\IOutputAwareInterface;
use HalloWelt\MediaWiki\Lib\Migration\Workspace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Output\Output;

class DokuwikiComposer extends ComposerBase implements IOutputAwareInterface {

	/** @var DataBuckets */
	private $dataBuckets;

	/** @var Output */
	private $output = null;

	/** @var array */
	private $advancedConfig = [];

	/**
	 * @param array $config
	 * @param Workspace $workspace
	 * @param DataBuckets $buckets
	 */
	public function __construct( $config, Workspace $workspace, DataBuckets $buckets ) {
		parent::__construct( $config, $workspace, $buckets );

		$this->dataBuckets = new DataBuckets( [
			'page-id-to-title-map',
			'page-id-to-page-contents',
			'page-id-to-attic-page-id',
			'page-id-to-attic-page-contents',
			'page-meta-map',
			'page-changes-map'
		] );

		$this->dataBuckets->loadFromWorkspace( $this->workspace );

		if ( isset( $this->config['config'] ) ) {
			$this->advancedConfig = $this->config['config'];
		}
	}

	/**
	 * @param Output $output
	 */
	public function setOutput( Output $output ) {
		$this->output = $output;
	}

	/**
	 * @param Builder $builder
	 * @return void
	 */
	public function buildXML( Builder $builder ) {
		$this->output->writeln( 'Appending default pages' );
		$this->appendDefaultPages( $builder );
		$this->output->writeln( 'Adding pages' );
		$this->addPages( $builder );
		$this->output->writeln( 'Adding default files' );
		$this->addDefaultFiles();
	}

	/**
	 * @param Builder $builder
	 * @return void
	 */
	private function addPages( Builder $builder ) {
		$pageIdToTitleMap = $this->dataBuckets->getBucketData( 'page-id-to-title-map' );
		$pageIdToAtticPageContents = $this->dataBuckets->getBucketData( 'page-id-to-attic-page-contents' );

		$titleToPageIdMap = array_flip( $pageIdToTitleMap );

		foreach ( $titleToPageIdMap as $pageTitle => $pageId ) {
			
			$key = str_replace( ':', '_', $pageId );
			$wikiText = $this->workspace->getConvertedContent( $key );

			$this->output->writeln( "Add latest revison of $pageTitle" );
			$builder->addRevision( $pageTitle, $wikiText );

			if ( !isset( $pageIdToAtticPageContents[$pageId] ) ) {
				continue;
			}

			$this->output->writeln( "Add latest history revison of $pageTitle" );

			$historyVersions = $pageIdToAtticPageContents[$pageId];
			foreach ( $historyVersions as $historyVersion ) {
				$paths = explode( '/', $historyVersion );
				$filename = array_pop( $paths );
				$filename = substr( $filename, 0, strlen( $filename ) - strlen( '.wiki' ) );
				$wikiText = $this->workspace->getConvertedContent( $filename );

				// unitx timestamp
				$filenamePageIdPart = str_replace( ':', '_', $pageId );
				$timestamp = str_replace( "$filenamePageIdPart.", '', $filename );

				$dateTime = date( 'Y-m-d H:i:s', (int)$timestamp );
				$this->output->writeln( "\t- $dateTime" );

				$mwTimestamp = date( 'YmdHis', (int)$timestamp );

				$username = '';
				$comment = '';

				// do not handle attic versions with .change information


				$builder->addRevision( $pageTitle, $wikiText, $mwTimestamp, $username, '', '', $comment );
			}
		}
	}

	/**
	 * @param Builder $builder
	 * @return void
	 */
	private function appendDefaultPages( Builder $builder ) {
		$basepath = __DIR__ . '/_defaultpages/';
		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $basepath ),
			RecursiveIteratorIterator::LEAVES_ONLY
		);

		foreach ( $files as $fileObj ) {
			if ( $fileObj->isDir() ) {
				continue;
			}
			$file = $fileObj->getPathname();
			$namespacePrefix = basename( dirname( $file ) );
			$pageName = basename( $file );
			$wikiPageName = "$namespacePrefix:$pageName";
			$wikiText = file_get_contents( $file );

			$builder->addRevision( $wikiPageName, $wikiText );
		}
	}

	/**
	 * @return void
	 */
	private function addDefaultFiles() {
		$basepath = __DIR__ . '/_defaultfiles/';
		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $basepath ),
			RecursiveIteratorIterator::LEAVES_ONLY
		);

		foreach ( $files as $fileObj ) {
			if ( $fileObj->isDir() ) {
				continue;
			}
			$file = $fileObj->getPathname();
			$fileName = basename( $file );
			$data = file_get_contents( $file );

			$this->workspace->saveUploadFile( $fileName, $data );
		}
	}

}
