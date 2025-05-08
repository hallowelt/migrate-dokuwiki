<?php

namespace HalloWelt\MigrateDokuwiki\Command;

use Exception;
use HalloWelt\MediaWiki\Lib\Migration\IFileProcessorEventHandler;
use HalloWelt\MediaWiki\Lib\Migration\IOutputAwareInterface;
use HalloWelt\MediaWiki\Lib\Migration\Workspace;
use HalloWelt\MigrateDokuwiki\IExtractor;
use SplFileInfo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class Extract extends Command {

	/** @var IExtractor[] */
	protected $extractors = [];

	/** @var IFileProcessorEventHandler */
	protected $eventhandlers = [];

	/**
	 * @var InputInterface
	 */
	private $input;

	/**
	 * @var OutputInterface
	 */
	private $output;

	/**
	 * @var array
	 */
	private $config;

	/** @var string */
	private $src;

	/** @var string */
	private $dest;

	/** @var Workspace */
	private $workspace;

	/**
	 *
	 * @inheritDoc
	 */
	protected function configure() {
		$this->setName( 'extract' );
		$this->setDefinition( new InputDefinition( [
				new InputOption(
					'src',
					null,
					InputOption::VALUE_REQUIRED,
					'Specifies the path to the input file or directory'
				),
				new InputOption(
					'dest',
					null,
					InputOption::VALUE_OPTIONAL,
					'Specifies the path to the output file or directory',
					'.'
				),
				new InputOption(
					'config',
					null,
					InputOption::VALUE_REQUIRED,
					'Specifies the path to the config yaml file'
				)
			] ) );
		return parent::configure();
	}

	/**
	 * @param array $config
	 */
	public function __construct( $config ) {
		parent::__construct();
		$this->config = $config;
	}

	/**
	 * @param array $config
	 * @return Extract
	 */
	public static function factory( $config ): Extract {
		return new static( $config );
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return void
	 */
	protected function execute( InputInterface $input, OutputInterface $output ) {
		$this->input = $input;
		$this->output = $output;

		$this->src = realpath( $this->input->getOption( 'src' ) );
		$this->dest = realpath( $this->input->getOption( 'dest' ) );

		$this->output->writeln( "Source: {$this->src}" );
		$this->output->writeln( "Destination: {$this->dest}\n" );

		$this->readConfigFile( $this->config );

		$this->beforeProcessFiles();
		$this->runBeforeProcessFilesEventHandlers();
		foreach ( $this->extractors as $key => $extractor ) {
			$result = $extractor->extract();
			// TODO: Evaluate result
		}
		$this->runAfterProcessFilesEventHandlers();

		$this->output->writeln( '<info>Done.</info>' );
	}

	/**
	 *
	 */
	protected function beforeProcessFiles() {
		$workspaceDir = new SplFileInfo( $this->dest );
		$this->workspace = new Workspace( $workspaceDir );

		$extractorFactoryCallbacks = $this->config['extractors'];
		foreach ( $extractorFactoryCallbacks as $key => $callback ) {
			$extractor = call_user_func_array(
				$callback,
				[ $this->config, $this->workspace ]
			);
			if ( $extractor instanceof IExtractor === false ) {
				throw new Exception(
					"Factory callback for extractor '$key' did not return an "
					. "IExtractor object"
				);
			}
			if ( $extractor instanceof IOutputAwareInterface ) {
				$extractor->setOutput( $this->output );
			}
			$this->extractors[$key] = $extractor;
			if ( $extractor instanceof IFileProcessorEventHandler ) {
				$this->eventhandlers[$key] = $extractor;
			}
		}
	}

	/**
	 *
	 */
	protected function runBeforeProcessFilesEventHandlers() {
		foreach ( $this->eventhandlers as $handler ) {
			$handler->beforeProcessFiles( new SplFileInfo( $this->src ) );
		}
	}

	/**
	 *
	 */
	protected function runAfterProcessFilesEventHandlers() {
		foreach ( $this->eventhandlers as $handler ) {
			$handler->afterProcessFiles( new SplFileInfo( $this->src ) );
		}
	}

	/**
	 * @return array
	 */
	protected function makeExtensionWhitelist(): array {
		if ( isset( $this->config['file-extension-whitelist' ] ) ) {
			return $this->config['file-extension-whitelist' ];
		}
		return [];
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
