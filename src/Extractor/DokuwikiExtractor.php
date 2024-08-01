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
		$titles = [];
		if ( isset( $pageTitles['pages_titles'] ) ) {
			$titles = $pageTitles['pages_titles'];
		}

		foreach ( $titles as $title ) {
			if ( isset( $pagesMap[$title] ) && !empty( $pagesMap[$title] ) ) {
				$filepath = $pagesMap[$title][0];
				$this->extractCurrentPageRevision( $title, $filepath );
			} else {
				continue;
			}

			if ( isset( $historyPageTitles[$title] ) && !empty( $historyPageTitles[$title] ) ) {
				foreach ( $historyPageTitles[$title] as $filepath ) {
					$this->extractHistoryPageRevision( $title, $filepath );
				}
			}
		}
	}

	/**
	 * @param string $title
	 * @param string $filepath
	 */
	private function extractCurrentPageRevision( $title, $filepath ) {
		$id = md5( $title );
		$content = file_get_contents( $filepath );
		$targetFileName = $this->workspace->saveRawContent( $id, $content );
		$this->buckets->addData( 'page-id-to-title-map', $id, $title, true, true );
		$this->output->writeln( $title );
	}

	/**
	 * @param string $title
	 * @param string $filepath
	 */
	private function extractHistoryPageRevision( $title, $filepath ) {
		$file = new SplFileInfo ( $filepath );
		$filename = $file->getFilename();
		$filenameParts = explode( '.', $filename );
		if ( count( $filenameParts ) < 3 ) {
			// A valid history version has at least name . timestamp . extnsion
			return;
		}
		$fileExtension = array_pop( $filenameParts );
		$timestamp = array_pop( $filenameParts );
		$id = md5( $title ) . ".$timestamp";
		$content = file_get_contents( $filepath );
		$targetFileName = $this->workspace->saveRawContent( $id, $content, 'content/history/raw' );
		$this->buckets->addData( 'page-id-to-attic-page-id', md5( $title ), $id, true, true );
		$this->output->writeln( $title );
	}
}