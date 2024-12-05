<?php

namespace HalloWelt\MigrateDokuwiki\Converter\PostProcessors\Table;

use HalloWelt\MigrateDokuwiki\IProcessor;

class RestoreTableWidth implements IProcessor {

	/**
	 * @param string $text
	 * @return string
	 */
	public function process( string $text ): string {
		$lines = explode( "\n", $text );

		$newLines = [];
		for ( $index = 0; $index < count( $lines ); $index++ ) {
			$line = $lines[$index];

			$startPos = strpos( $line, '###PRESERVETABLEWIDTHSTART###' );
			if ( is_int( $startPos ) ) {
				$start = $startPos + strlen( '###PRESERVETABLEWIDTHSTART###' );

				$stopPos = strpos( $line, '###PRESERVETABLEWIDTHEND###' );

				$data = substr( $line, $start, $stopPos - $start );

				$replacement = '<span class="plugin-table-width" style="display: none;">';
				$replacement .= $data;
				$replacement .= '</span>';

				$toReplace = substr(
					$line,
					$startPos,
					$stopPos - $startPos + strlen( '###PRESERVETABLEWIDTHEND###' )
				);
				$newLine = str_replace( $toReplace, $replacement, $line );
				$newLines[] = $newLine;
			} else {
				$newLines[] = $line;
			}
		}

		return implode( "\n", $newLines );
	}

}
