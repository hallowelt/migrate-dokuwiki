<?php

namespace HalloWelt\MigrateDokuwiki\Converter\PreProcessors\Table;

use HalloWelt\MigrateDokuwiki\IProcessor;

class Colspan implements IProcessor {

	/**
	 * @param string $text
	 * @return string
	 */
	public function process( string $text ): string {
		$lines = explode( "\n", $text );

		foreach ( $lines as $index => &$line ) {
			$line = trim( $line );

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

			$regex = '/(.*?)(\|\|+)/';
			$line = preg_replace_callback( $regex, static function( $matches ) {
				$colspanCount = strlen( $matches[2] );

				return $matches[1] . '###COLSPAN_' . $colspanCount . '###|';
			}, $line );
		}
		unset( $line );

		$text = implode( "\n", $lines );

		return $text;
	}
}