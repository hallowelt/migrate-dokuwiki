<?php

namespace HalloWelt\MigrateDokuwiki\Converter\PreProcessors\Table;

use HalloWelt\MigrateDokuwiki\IProcessor;

class RemoveLinebreakBeforeTable implements IProcessor {

	/**
	 * Remove linebreak (\\) before table
	 *
	 * @param string $text
	 * @param string $path
	 * @return string
	 */
	public function process( string $text, string $path = '' ): string {
		$lines = explode( "\n", $text );

		for ( $index = 1; $index < count( $lines ); $index++ ) {
			$line = $lines[$index];
			// Each table has either "|" or "^" at the line start
			if (
				strpos( $line, "|" ) !== 0 &&
				strpos( $line, "^" ) !== 0
			) {
				continue;
			}

			$lastLine = $lines[$index - 1];
			$lastLine = trim( $lastLine );

			if (
				strpos( $lastLine, "|" ) === 0 ||
				strpos( $lastLine, "^" ) === 0
			) {
				// Last line is a row line with linebreak at the end.
				// This line will be handled by RemoveLinebreakAtEndOfRow preprocessor.
				continue;
			}

			if ( strpos( $lastLine, '\\' ) === strlen( $lastLine ) - 2 ) {
				$lines[$index - 1] = substr( $lastLine, 0, strlen( $lastLine ) - 2 );
			}
		}

		$text = implode( "\n", $lines );

		return $text;
	}
}
