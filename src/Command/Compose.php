<?php

namespace HalloWelt\MigrateDokuwiki\Command;

use Exception;
use HalloWelt\MediaWiki\Lib\MediaWikiXML\Builder;
use HalloWelt\MediaWiki\Lib\Migration\CliCommandBase;
use HalloWelt\MediaWiki\Lib\Migration\DataBuckets;
use HalloWelt\MediaWiki\Lib\Migration\IComposer;
use HalloWelt\MediaWiki\Lib\Migration\IOutputAwareInterface;
use HalloWelt\MediaWiki\Lib\Migration\Workspace;
use SplFileInfo;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class Compose extends CliCommandBase {
	/**
	 *
	 * @param array $config
	 * @return IConverter
	 */
	public static function factory( $config ) {
		return new static( $config );
	}

	/**
	 * @return void
	 */
	protected function configure() {
		$this->setName( 'compose' );
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

	protected function makeFileList() {
		return [];
	}

	protected function processFiles() {
		$this->readConfigFile( $this->config );
		$this->ensureTargetDirs();
		$this->workspace = new Workspace( new SplFileInfo( $this->src ) );
		$this->buckets = new DataBuckets( [] );
		$this->buckets->loadFromWorkspace( $this->workspace );
		$composers = $this->makeComposers();
		$mediawikixmlbuilder = new Builder();
		foreach ( $composers as $composer ) {
			$composer->buildXML( $mediawikixmlbuilder );
		}
		$mediawikixmlbuilder->buildAndSave( $this->dest . '/result/output.xml' );
	}

	/**
	 *
	 * @return IComposer[]
	 */
	protected function makeComposers() {
		$composers = [];
		$composerCallbacks = $this->config['composers'];
		foreach ( $composerCallbacks as $key => $callback ) {
			$composer = call_user_func_array(
				$callback,
				[ $this->config, $this->workspace, $this->buckets ]
			);
			if ( $composer instanceof IComposer === false ) {
				throw new Exception(
					"Factory callback for analyzer '$key' did not return an "
					. "IComposer object"
				);
			}
			if ( $composer instanceof IOutputAwareInterface ) {
				$composer->setOutput( $this->output );
			}
			$composers[] = $composer;
		}

		return $composers;
	}

	protected function doProcessFile(): bool {
		// Do nothing
		return true;
	}

	private function ensureTargetDirs() {
		$path = "{$this->dest}/result/images";
		if ( !file_exists( $path ) ) {
			mkdir( $path, 0755, true );
		}
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
