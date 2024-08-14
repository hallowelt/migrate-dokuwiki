<?php

namespace HalloWelt\MigrateDokuwiki\Converter;

use HalloWelt\MediaWiki\Lib\Migration\DataBuckets;
use HalloWelt\MediaWiki\Lib\Migration\IOutputAwareInterface;
use HalloWelt\MediaWiki\Lib\Migration\Workspace;
use HalloWelt\MigrateDokuwiki\Converter\PostProcessors\Image as ImagePostProcessor;
use HalloWelt\MigrateDokuwiki\Converter\Processors\Image as ImageProcessor;
use HalloWelt\MigrateDokuwiki\Converter\Processors\Link;
use HalloWelt\MigrateDokuwiki\IProcessor;
use SplFileInfo;
use Symfony\Component\Console\Output\Output;

class DokuwikiConverter extends PandocDokuwiki implements IOutputAwareInterface {

	/** @var DataBuckets */
	private $dataBuckets;

	/** @var Output */
	private $output;

	/**
	 * @return array
	 */
	private function getPreProcessors(): array {
		return [];
	}

	/**
	 * @return array
	 */
	private function getProcessors(): array {
		return [
			new Link( $this->dataBuckets->getBucketData( 'page-key-to-title-map' ) ),
			new ImageProcessor( $this->dataBuckets->getBucketData( 'media-key-to-title-map' ) )
		];
	}

	/**
	 * @return array
	 */
	private function getPostProcessors(): array {
		return [
			new ImagePostProcessor()
		];
	}

	/**
	 * @return array
	 */
	private function getBucketKeys(): array {
		return [
			// From this step
			'namespaces-map',
			'pages-map',
			'page-titles',
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
			'page-key-to-title-map',
		];
	}

	/**
	 *
	 * @param array $config
	 * @param Workspace $workspace
	 */
	public function __construct( $config, Workspace $workspace ) {
		parent::__construct( $config, $workspace );

		$this->dataBuckets = new DataBuckets(
			$this->getBucketKeys()
		);
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
		$rawPathname = $file->getPathname();
		if ( !file_exists( $rawPathname ) ) {
			return '';
		}

		$this->output->writeln( $rawPathname );

		$content = file_get_contents( $rawPathname );
		if ( !$content ) {
			return '';
		}

		$content = $this->runPreProcessors( $content );
		$content = $this->runProcessors( $content );
		$prepPathname = str_replace( '.mraw', '.mprep', $rawPathname );
		file_put_contents( $prepPathname, $content );

		$prepFile = new SplFileInfo( $prepPathname );
		$wikiText = parent::doConvert( $prepFile );

		$wikiText = $this->runPostProcessors( $wikiText );

		$wikiText = $this->decorateWikiText( $wikiText );

		return $wikiText;
	}

	/**
	 * @param string $text
	 * @return string
	 */
	private function runPreProcessors( string $text ): string {
		$preProcessors = $this->getPreProcessors();

		/** @var IProcessor $processor */
		foreach ( $preProcessors as $preProcessor ) {
			$text = $preProcessor->process( $text );
		}

		return $text;
	}

	/**
	 * @param string $text
	 * @return string
	 */
	private function runProcessors( string $text ): string {
		$processors = $this->getProcessors();

		/** @var IProcessor $processor */
		foreach ( $processors as $processor ) {
			$text = $processor->process( $text );
		}

		return $text;
	}

	/**
	 * @param string $text
	 * @return string
	 */
	private function runPostProcessors( string $text ): string {
		$postProcessors = $this->getPostProcessors();

		/** @var IProcessor $postProcessor */
		foreach ( $postProcessors as $postProcessor ) {
			$text = $postProcessor->process( $text );
		}

		return $text;
	}

	/**
	 * @param string $text
	 * @return string
	 */
	private function decorateWikiText( string $text ): string {
		return $text;
	}

	/**
	 * @return string
	 */
	private function getCurrentPageTitle(): string {
		// $spaceIdPrefixMap = $this->dataBuckets->getBucketData( 'pages-map' );
		$currentPageTitle = '';
		return $currentPageTitle;
	}
}
