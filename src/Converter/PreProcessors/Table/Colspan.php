<?php

namespace HalloWelt\MigrateDokuwiki\Converter\PreProcessors\Table;

use HalloWelt\MigrateDokuwiki\IProcessor;
use HalloWelt\MigrateDokuwiki\Utility\CategoryBuilder;

class Colspan implements IProcessor {

	/**
	 * @param string $text
	 * @param string $path
	 * @return string
	 */
	public function process( string $text, string $path = '' ): string {
		$lines = explode( "\n", $text );

		foreach ( $lines as $index => &$line ) {
			// Each table has either "|" or "^" at the line start
			if (
				strpos( $line, "|" ) === 0 ||
				strpos( $line, "^" ) === 0
			) {
				$isTable = true;
			} else {
				$isTable = false;
			}

			if ( !$isTable ) {
				continue;
			}

			$originalLine = $line;

			$regex = '/(.*?)(\|\|+)/';
			$line = preg_replace_callback( $regex, static function ( $matches ) {
				$colspanCount = strlen( $matches[2] );

				return $matches[1] . '###COLSPAN_' . $colspanCount . '###|' . str_repeat( '|', $colspanCount - 1 );
			}, $line );

			if ( !is_string( $line ) ) {
				$category = CategoryBuilder::getPreservedMigrationCategory( 'Colspan failure' );
				$line = "{$originalLine} {$category}";
			}
		}
		unset( $line );

		$text = implode( "\n", $lines );

		return $text;
	}
}
