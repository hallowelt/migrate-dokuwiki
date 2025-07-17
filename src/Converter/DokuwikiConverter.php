<?php

namespace HalloWelt\MigrateDokuwiki\Converter;

use HalloWelt\MediaWiki\Lib\Migration\DataBuckets;
use HalloWelt\MediaWiki\Lib\Migration\IOutputAwareInterface;
use HalloWelt\MediaWiki\Lib\Migration\Workspace;
use HalloWelt\MigrateDokuwiki\Converter\PostProcessors\Color;
use HalloWelt\MigrateDokuwiki\Converter\PostProcessors\Displaytitle;
use HalloWelt\MigrateDokuwiki\Converter\PostProcessors\FontSize;
use HalloWelt\MigrateDokuwiki\Converter\PostProcessors\Hidden;
use HalloWelt\MigrateDokuwiki\Converter\PostProcessors\Image as ImagePostProcessor;
use HalloWelt\MigrateDokuwiki\Converter\PostProcessors\Link as LinkPostProcessor;
use HalloWelt\MigrateDokuwiki\Converter\PostProcessors\RestoreCategories;
use HalloWelt\MigrateDokuwiki\Converter\PostProcessors\RestoreCode;
use HalloWelt\MigrateDokuwiki\Converter\PostProcessors\RestoreImageCaption;
use HalloWelt\MigrateDokuwiki\Converter\PostProcessors\RestoreInclude;
use HalloWelt\MigrateDokuwiki\Converter\PostProcessors\RestoreIndexMenu;
use HalloWelt\MigrateDokuwiki\Converter\PostProcessors\RestoreWrap;
use HalloWelt\MigrateDokuwiki\Converter\PostProcessors\Table\Colspan as ColspanPostProcessor;
use HalloWelt\MigrateDokuwiki\Converter\PostProcessors\Table\RestoreTableWidth;
use HalloWelt\MigrateDokuwiki\Converter\PostProcessors\Table\Rowspan as RowspanPostProcessor;
use HalloWelt\MigrateDokuwiki\Converter\PreProcessors\EmoticonsAndSymbols;
use HalloWelt\MigrateDokuwiki\Converter\PreProcessors\EnsureListIndention;
use HalloWelt\MigrateDokuwiki\Converter\PreProcessors\PreserveCode;
use HalloWelt\MigrateDokuwiki\Converter\PreProcessors\PreserveImageCaption;
use HalloWelt\MigrateDokuwiki\Converter\PreProcessors\PreserveIndexMenu;
use HalloWelt\MigrateDokuwiki\Converter\PreProcessors\PreserveWrap;
use HalloWelt\MigrateDokuwiki\Converter\PreProcessors\RemoveLinebreakBeforeListItems;
use HalloWelt\MigrateDokuwiki\Converter\PreProcessors\Table\Colspan as ColspanPreProcessor;
use HalloWelt\MigrateDokuwiki\Converter\PreProcessors\Table\PreserveTableWidth;
use HalloWelt\MigrateDokuwiki\Converter\PreProcessors\Table\RemoveLinebreakAtEndOfRow;
use HalloWelt\MigrateDokuwiki\Converter\PreProcessors\Table\RemoveLinebreakBeforeTable;
use HalloWelt\MigrateDokuwiki\Converter\Processors\Image as ImageProcessor;
use HalloWelt\MigrateDokuwiki\Converter\Processors\Link;
use HalloWelt\MigrateDokuwiki\Converter\Processors\PreserveInclude;
use HalloWelt\MigrateDokuwiki\IProcessor;
use SplFileInfo;
use Symfony\Component\Console\Output\Output;

class DokuwikiConverter extends PandocDokuwiki implements IOutputAwareInterface {

	/** @var DataBuckets */
	private $dataBuckets;

	/** @var Output */
	private $output;

	/** @var array */
	private $advancedConfig = [];

	/**
	 * @return array
	 */
	private function getPreProcessors(): array {
		return [
			new EmoticonsAndSymbols( $this->advancedConfig ),
			new RemoveLinebreakBeforeListItems(),
			new RemoveLinebreakBeforeTable(),
			new RemoveLinebreakAtEndOfRow(),
			new PreserveCode(),
			new PreserveTableWidth(),
			new ColspanPreProcessor(),
			new PreserveIndexMenu(),
			new PreserveWrap(),
			new PreserveImageCaption(),
			new EnsureListIndention(),
		];
	}

	/**
	 * @return array
	 */
	private function getProcessors(): array {
		return [
			new PreserveInclude( $this->dataBuckets->getBucketData( 'page-id-to-title-map' ) ),
			new Link( $this->dataBuckets->getBucketData( 'page-id-to-title-map' ) ),
			new ImageProcessor( $this->dataBuckets->getBucketData( 'media-id-to-title-map' ), $this->advancedConfig )
		];
	}

	/**
	 * @return array
	 */
	private function getPostProcessors(): array {
		return [
			new Displaytitle(),
			new RestoreImageCaption(),
			new ImagePostProcessor( $this->advancedConfig ),
			new LinkPostProcessor(),
			new Color(),
			new FontSize(),
			new Hidden(),
			new RestoreWrap(),
			new ColspanPostProcessor(),
			new RowspanPostProcessor(),
			new RestoreTableWidth(),
			new RestoreIndexMenu( $this->dataBuckets->getBucketData( 'media-id-to-title-map' ) ),
			new RestoreCode(),
			new RestoreCategories(),
			new RestoreInclude(),
		];
	}

	/**
	 * @return array
	 */
	private function getBucketKeys(): array {
		return [
			'pages-map',
			'attic-pages-map',
			'page-meta-map',
			'page-changes-map',
			'media-map',
			'attic-media-map',
			'page-id-to-title-map',
			'media-id-to-title-map',
			// From this step
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

		$this->config = $config;
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
	 * @inheritDoc
	 */
	protected function doConvert( SplFileInfo $file ): string {
		$rawPathname = $file->getPathname();
		if ( !file_exists( $rawPathname ) ) {
			echo "File does not exist: $rawPathname";
			return '';
		}

		$this->output->writeln( $rawPathname );

		$content = file_get_contents( $rawPathname );
		if ( !$content ) {
			return '';
		}

		$content = $this->runPreProcessors( $content, $rawPathname );
		$content = $this->runProcessors( $content, $rawPathname );
		$prepPathname = str_replace( '.mraw', '.mprep', $rawPathname );
		file_put_contents( $prepPathname, $content );

		$prepFile = new SplFileInfo( $prepPathname );
		$wikiText = parent::doConvert( $prepFile );

		$wikiText = $this->runPostProcessors( $wikiText, $rawPathname );

		$wikiText = $this->decorateWikiText( $wikiText );

		return $wikiText;
	}

	/**
	 * @param string $text
	 * @param string $path
	 * @return string
	 */
	private function runPreProcessors( string $text, string $path ): string {
		$preProcessors = $this->getPreProcessors();

		/** @var IProcessor $processor */
		foreach ( $preProcessors as $preProcessor ) {
			$text = $preProcessor->process( $text, $path );
		}

		return $text;
	}

		/**
		 * @param string $text
		 * @param string $path
		 * @return string
		 */
	private function runProcessors( string $text, string $path ): string {
		$processors = $this->getProcessors();

		/** @var IProcessor $processor */
		foreach ( $processors as $processor ) {
			$text = $processor->process( $text, $path );
		}

		return $text;
	}

	/**
	 * @param string $text
	 * @param string $path
	 * @return string
	 */
	private function runPostProcessors( string $text, $path ): string {
		$postProcessors = $this->getPostProcessors();

		/** @var IProcessor $postProcessor */
		foreach ( $postProcessors as $postProcessor ) {
			$text = $postProcessor->process( $text, $path );
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
}
