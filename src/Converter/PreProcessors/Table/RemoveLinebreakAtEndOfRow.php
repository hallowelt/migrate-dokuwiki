<?php

namespace HalloWelt\MigrateDokuwiki\Converter\PreProcessors\Table;

use HalloWelt\MigrateDokuwiki\IProcessor;

class RemoveLinebreakAtEndOfRow implements IProcessor {

	/**
	 * Remove linebreak (\\) after table row
	 *
	 * @param string $text
	 * @param string $path
	 * @return string
	 */
	public function process( string $text, string $path = '' ): string {
		$lines = explode( "\n", $text );

		foreach ( $lines as $index => &$line ) {
			// Each table has either "|" or "^" at the line start
			if (
				strpos( $line, "|" ) !== 0 &&
				strpos( $line, "^" ) !== 0
			) {
				continue;
			}

			$trimLine = trim( $line, ' \\\\ ' );
			if ( $trimLine !== $line ) {
				$line = $trimLine;
			}
		}

		$text = implode( "\n", $lines );

		return $text;
	}
}
