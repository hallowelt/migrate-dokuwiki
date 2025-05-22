<?php

namespace HalloWelt\MigrateDokuwiki\Converter\PreProcessors;

use HalloWelt\MigrateDokuwiki\Utility\CategoryBuilder;
use HalloWelt\MigrateDokuwiki\IProcessor;

class EmoticonsAndSymbols implements IProcessor {

	/** @var array */
	private $advancedConfig = [];

	public function __construct( array $advancedConfig ) {
		$this->advancedConfig = $advancedConfig;
	}
	/**
	 * @param string $text
	 * @param string $path
	 * @return string
	 */
	public function process( string $text, string $path = '' ): string {
		$originalText = $text;

		$symbolsMap = $this->getSymbolsMap();
		foreach ( $symbolsMap as $symbol => $replacement ) {
			$regEx = preg_quote( $symbol );

			$text = preg_replace(
				"#$regEx#",
				$replacement,
				$text
			);
		}

		if ( !is_string( $text ) ) {
			$category = CategoryBuilder::getPreservedMigrationCategory( 'EmoticonsAndSymbols failure' );
			$text = "{$originalText} {$category}";
		}

		return $text;
	}

	/**
	 * @return array
	 */
	private function getSymbolsMap(): array {
		$defaultSymbols = [
			'8-)' => '&#x1F60E;',
			'8-O' => '&#x1F60A;',
			':-(' => '&#x1F641;',
			':-)' => '&#x1F642;',
			'=)' => '&#x1F600;',
			':-/' => '&#x1F615;',
			':-\\' => '&#x1F615;',
			':-?' => '&#x1F616;',
			':-D' => '&#x1F601;',
			':-P' => '&#x1F61B;',
			':-O' => '&#x1F62F;',
			':-X' => '&#x1F910;',
			':-|' => '&#x1F610;',
			';-)' => '&#x1F609;',
			'^_^' => '&#x1F601;',
			':?:' => '&#x2753;',
			':!:' => '&#x2757;',
			'LOL' => '&#x1F923;',
			'FIXME' => '<span style="padding: 2px; background-color:yellow;">&#x1F527; FIXME</span>',
			'DELETEME' => '<span style="padding: 2px; background-color:yellow;">&#x1F5D1; DELETEME</span>'
		];

		if ( isset( $this->advancedConfig['custom-symbols'] ) &&
			is_array( $this->advancedConfig['custom-symbols'] ) &&
			!empty( $this->advancedConfig['custom-symbols'] )
		) {
			$customSymbols = array_merge(
				$defaultSymbols,
				$this->advancedConfig['custom-symbols']
			);

			return $customSymbols;
		}

		return $defaultSymbols;
	}
}