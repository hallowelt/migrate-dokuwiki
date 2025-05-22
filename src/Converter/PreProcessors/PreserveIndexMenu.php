<?php

namespace HalloWelt\MigrateDokuwiki\Converter\PreProcessors;

use HalloWelt\MigrateDokuwiki\IProcessor;
use HalloWelt\MigrateDokuwiki\Utility\CategoryBuilder;

class PreserveIndexMenu implements IProcessor {

	/**
	 * https://www.dokuwiki.org/plugin:indexmenu
	 *
	 * @param string $text
	 * @param string $path
	 * @return string
	 */
	public function process( string $text, string $path = '' ): string {
		$text = $this->preserveMetaSort( $text );
		$text = $this->preserveView( $text );

		return $text;
	}

	/**
	 * @param string $text
	 * @return string
	 */
	private function preserveMetaSort( string $text ): string {
		$originalText = $text;

		$regEx = '#\{\{indexmenu_n>(.*?)\}\}#';
		$text = preg_replace_callback( $regEx, static function ( $matches ) {
			$replacement = $matches[0];

			$replacement = '######PRESERVEINDEXMENUMETASORTSTART######';
			$replacement .= $matches[1];
			$replacement .= '######PRESERVEINDEXMENUMETASORTEND######';
			return $replacement;
		}, $text );

		if ( !is_string( $text ) ) {
			$category = CategoryBuilder::getPreservedMigrationCategory( 'Indexmenu meta sort failure' );
			$text = "{$originalText} {$category}";
		}

		return $text;
	}

	/**
	 * {{indexmenu>:test:page#1}}
	 * {{indexmenu>.#4|js nsort tsort msort navbar notoc noscroll}}
	 * {{indexmenu>#1|js nsort tsort msort navbar notoc noscroll}}
	 * {{indexmenu>.#1|js custom_sort:page|param 1|param 2 nsort noscroll navbar notoc}}
	 *
	 * @param string $text
	 * @return string
	 */
	private function preserveView( string $text ): string {
		$originalText = $text;

		$regEx = '#\{\{indexmenu>(.*?)\}\}#';
		$text = preg_replace_callback( $regEx, static function ( $matches ) {
			$replacement = '######PRESERVEINDEXMENUSTART######';
			$replacement .= $matches[1];
			$replacement .= '######PRESERVEINDEXMENEND######';
			return $replacement;
		}, $text );

		if ( !is_string( $text ) ) {
			$category = CategoryBuilder::getPreservedMigrationCategory( 'Indexmenu view failure' );
			$text = "{$originalText} {$category}";
		}

		return $text;
	}
}
