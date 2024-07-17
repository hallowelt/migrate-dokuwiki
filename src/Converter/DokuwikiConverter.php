<?php

namespace HalloWelt\MigrateDokuwiki\Converter;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use HalloWelt\MediaWiki\Lib\Migration\Converter\PandocHTML;
use HalloWelt\MediaWiki\Lib\Migration\DataBuckets;
use HalloWelt\MediaWiki\Lib\Migration\IOutputAwareInterface;
use HalloWelt\MediaWiki\Lib\Migration\Workspace;
use SplFileInfo;
use Symfony\Component\Console\Output\Output;

class DokuwikiConverter extends PandocHTML implements IOutputAwareInterface {

	/** @var bool */
	protected $bodyContentFile = null;

	/**
	 * @var DataBuckets
	 */
	private $dataBuckets = null;

	/**
	 *
	 * @var ConversionDataLookup
	 */
	private $dataLookup = null;

	/**
	 * @var ConversionDataWriter
	 */
	private $conversionDataWriter = null;

	/**
	 *
	 * @var SplFileInfo
	 */
	private $rawFile = null;

	/**
	 * @var string
	 */
	private $wikiText = '';

	/**
	 * @var string
	 */
	private $currentPageTitle = '';

	/** @var int */
	private $currentSpace = 0;

	/**
	 *
	 * @var SplFileInfo
	 */
	private $preprocessedFile = null;

	/**
	 * @var Output
	 */
	private $output = null;

	/**
	 * @var bool
	 */
	private $nsFileRepoCompat = false;

	/**
	 *
	 * @param array $config
	 * @param Workspace $workspace
	 */
	public function __construct( $config, Workspace $workspace ) {
		parent::__construct( $config, $workspace );

		$this->dataBuckets = new DataBuckets( [
		
		] );

		$this->dataBuckets->loadFromWorkspace( $this->workspace );
	}

	/**
	 * @param Output $output
	 */
	public function setOutput( Output $output ) {
		$this->output = $output;
	}

	/**
	 * @inheritDoc
	 */
	protected function doConvert( SplFileInfo $file ): string {
		$this->output->writeln( $file->getPathname() );
		$this->dataLookup = ConversionDataLookup::newFromBuckets( $this->dataBuckets );
		$this->conversionDataWriter = ConversionDataWriter::newFromBuckets( $this->dataBuckets );
		
		$this->runProcessors( $dom );

		$this->wikiText = parent::doConvert( $this->preprocessedFile );

		$this->runPostProcessors();

		$this->postprocessWikiText();

		return $this->wikiText;
	}

	/**
	 *
	 * @param DOMDocument $dom
	 * @return void
	 */
	private function runProcessors( $dom ) {
		$currentPageTitle = $this->getCurrentPageTitle();

		$processors = [
			
		];

		/** @var IProcessor $processor */
		foreach ( $processors as $processor ) {
			$processor->process( $dom );
		}
	}

	/**
	 *
	 * @return void
	 */
	private function runPostProcessors() {
		$postProcessors = [
			
		];

		/** @var IPostprocessor $postProcessor */
		foreach ( $postProcessors as $postProcessor ) {
			$this->wikiText = $postProcessor->postprocess( $this->wikiText );
		}
	}

	/**
	 * @return string
	 */
	private function getCurrentPageTitle(): string {
		$spaceIdPrefixMap = $this->dataBuckets->getBucketData( 'pages-map' );
		$prefix = $spaceIdPrefixMap[$this->currentSpace];
		$currentPageTitle = $this->currentPageTitle;

		if ( substr( $currentPageTitle, 0, strlen( "$prefix:" ) ) === "$prefix:" ) {
			$currentPageTitle = str_replace( "$prefix:", '', $currentPageTitle );
		}

		return $currentPageTitle;
	}
}
