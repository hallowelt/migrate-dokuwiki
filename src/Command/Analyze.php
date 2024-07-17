<?php

namespace HalloWelt\MigrateDokuwiki\Command;

use HalloWelt\MediaWiki\Lib\Migration\CliCommandBase;
use HalloWelt\MediaWiki\Lib\Migration\DataBuckets;
use HalloWelt\MediaWiki\Lib\Migration\IAnalyzer;
use HalloWelt\MediaWiki\Lib\Migration\IOutputAwareInterface;
use HalloWelt\MigrateDokuwiki\ISourcePathAwareInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class Analyze extends CliCommandBase {

	/**
	 *
	 * @inheritDoc
	 */
	protected function configure() {
		$this->setName( 'analyze' );

		$config = parent::configure();

		/** @var InputDefinition */
		$definition = $this->getDefinition();
		$definition->addOption(
			new InputOption(
				'config',
				null,
				InputOption::VALUE_REQUIRED,
				'Specifies the path to the config yaml file'
			)
		);

		return $config;
	}

	/**
	 *
	 * @inheritDoc
	 */
	protected function getBucketKeys() {
		return [
		];
	}

	protected function beforeProcessFiles() {
		parent::beforeProcessFiles();
		// Explicitly reset the persisted data
		$this->buckets = new DataBuckets( $this->getBucketKeys() );
	}

	protected function doProcessFile(): bool {
		$this->readConfigFile( $this->config );

		$analyzerFactoryCallbacks = $this->config['analyzers'];
		foreach ( $analyzerFactoryCallbacks as $key => $callback ) {
			$analyzer = call_user_func_array(
				$callback,
				[ $this->config, $this->workspace, $this->buckets ]
			);
			if ( $analyzer instanceof IAnalyzer === false ) {
				throw new \Exception(
					"Factory callback for analyzer '$key' did not return an "
					. "IAnalyzer object"
				);
			}
			if ( $analyzer instanceof IOutputAwareInterface ) {
				$analyzer->setOutput( $this->output );
			}
			if ( $analyzer instanceof ISourcePathAwareInterface ) {
				$analyzer->setSourcePath( $this->src );
			}
			$result = $analyzer->analyze( $this->currentFile );
			// TODO: Evaluate result
		}
		return true;
	}

	/**
	 * @param array $config
	 * @return Analyze
	 */
	public static function factory( $config ): Analyze {
		return new static( $config );
	}

	/**
	 * @param array &$config
	 * @return void
	 */
	private function readConfigFile( &$config ): void {
		$filename = $this->input->getOption( 'config' );
		if ( is_file( $filename ) ) {
			$content = file_get_contents( $filename );
			if ( $content ) {
				try {
					$yaml = Yaml::parse( $content );
					$config = array_merge( $config, $yaml );
				} catch ( ParseException $e ) {
				}
			}
		}
	}
}
