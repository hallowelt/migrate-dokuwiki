<?php

namespace HalloWelt\MigrateDokuwiki\Converter\PostProcessors;

use HalloWelt\MigrateDokuwiki\IProcessor;
use HalloWelt\MigrateDokuwiki\Utility\CategoryBuilder;

class RestoreIndexMenu implements IProcessor {

	/** @var array */
	private $pageKeyToTitleMap;

	/**
	 * @param array $pageKeyToTitleMap
	 */
	public function __construct( array $pageKeyToTitleMap = [] ) {
		$this->pageKeyToTitleMap = $pageKeyToTitleMap;
	}

	/**
	 * @param string $text
	 * @param string $path
	 * @return string
	 */
	public function process( string $text, string $path = '' ): string {
		$text = $this->restoreMetaSort( $text );
		$text = $this->restoreView( $text );
		return $text;
	}

	/**
	 * @param string $text
	 * @return string
	 */
	private function restoreMetaSort( string $text ): string {
		$originalText = $text;

		$text = preg_replace(
			[
				'/######PRESERVEINDEXMENUMETASORTSTART######/',
				'/######PRESERVEINDEXMENUMETASORTEND######/'
			],
			[
				'<span class="indexmenu_n" style="display: none;">[[Indexmenu_n::',
				']]</span>'
			],
			$text
		);

		if ( !is_string( $text ) ) {
			$category = CategoryBuilder::getPreservedMigrationCategory( 'Indexment meta sort failure' );
			$text = "{$originalText} {$category}";
		}

		return $text;
	}

		/**
		 * @param string $text
		 * @return string
		 */
	private function restoreView( string $text ): string {
		$originalText = $text;

		$text = preg_replace_callback(
			'/######PRESERVEINDEXMENUSTART######(.*?)######PRESERVEINDEXMENEND######/',
			function ( $matches ) {
				$replacement = $matches[0];

				$range = '';
				$options = '';
				$hasOptions = strpos( $matches[1], '|' );
				if ( is_int( $hasOptions ) ) {
					$range = substr( $matches[1], 0, $hasOptions );
					$options = substr( $matches[1], $hasOptions + 1 );
					$options = str_replace( '|', ';', $options );
				} else {
					$range = $matches[1];
				}

				$src = '';
				$number = '';
				$hasNumber = strpos( $range, '#' );
				if ( is_int( $hasNumber ) ) {
					$src = substr( $range, 0, $hasNumber );
					$number = substr( $range, $hasNumber + 1 );
				} else {
					$src = $range;
				}

				$key = mb_strtolower( trim( $src, ':' ) );
				if ( isset( $this->pageKeyToTitleMap[$key] ) ) {
					$src = $this->pageKeyToTitleMap[$key];
				}

				$replacement = '{{IndexMenu|src=' . $src . '|number=' . $number . '|options=' . $options . '}}';

				return $replacement;
			}, $text
		);

		if ( !is_string( $text ) ) {
			$category = CategoryBuilder::getPreservedMigrationCategory( 'Indexmenu view failure' );
			$text = "{$originalText} {$category}";
		}

		return $text;
	}

}
