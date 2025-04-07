<?php

namespace HalloWelt\MigrateDokuwiki\Converter\PreProcessors\Table;

use HalloWelt\MigrateDokuwiki\IProcessor;

class PreserveTableWidth implements IProcessor {

	/**
	 * https://www.dokuwiki.org/plugin:tablewidth
	 *
	 * @param string $text
	 * @param string $path
	 * @return string
	 */
	public function process( string $text, string $path = '' ): string {
		$lines = explode( "\n", $text );

		$newLines = [];
		for ( $index = 0; $index < count( $lines ); $index++ ) {
			$line = $lines[$index];

			$start = strpos( $line, '|<' );
			$nowiki = strpos( $line, '|</nowiki>' );
			if ( is_int( $start ) && !$nowiki ) {
				if ( !isset( $lines[$index + 1] ) ) {
					$newLines[] = $line;
					// last line, not table follows
					continue;
				}

				if ( is_bool( strpos( $lines[$index + 1], '^' ) ) ) {
					// next line is not a table
					$newLines[] = $line;
					continue;
				}

				$end = strpos( $line, '>|' );

				$width = substr( $line, $start + 2, $end - $start - 2 );
				$width = preg_replace( '#\s+#', '|', $width );
				$width = trim( $width, '|' );
				$newLine = str_replace(
					substr( $line, $start, $end + 2 ),
					'###PRESERVETABLEWIDTHSTART###' . $width . '###PRESERVETABLEWIDTHEND###',
					$line
				);
				$newLines[] = $newLine;
			} else {
				$newLines[] = $line;
			}
		}

		return implode( "\n", $newLines );
	}
}
