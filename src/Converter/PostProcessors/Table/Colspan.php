<?php

namespace HalloWelt\MigrateDokuwiki\Converter\PostProcessors\Table;

use HalloWelt\MigrateDokuwiki\IProcessor;

class Colspan implements IProcessor {

	/**
	 * @param string $text
	 * @return string
	 */
	public function process( string $text ): string {
		$lines = explode( "\n", $text );

		foreach ( $lines as $lineIndex => &$line ) {
			$line = trim( $line );

			$colspanPos = strpos( $line, "###COLSPAN_" );
			if ( $colspanPos !== false ) {
				// In wikitext one line = one cell
				// One cell may have few blocks separated by "|"
				$cellBlocks = explode( '|', $line );

				$colspanCount = 0;

				foreach ( $cellBlocks as $cellBlockIndex => $cellBlock ) {
					$matches = [];

					preg_match( "/###COLSPAN_(.*?)###/", $cellBlock, $matches );

					// Current cell block contains "colspan"
					if ( isset( $matches[1] ) ) {
						$colspanCount = (int)$matches[1];

						// If cell already contains block with HTML attributes (like "style" or "colspan")
						// Then just append "colspan" there
						if ( count( $cellBlocks ) > 2 ) {
							$cellBlocks[$cellBlockIndex - 1] = $cellBlocks[$cellBlockIndex - 1] . " colspan=\"$colspanCount\"";
						} else {
							// Otherwise add such block
							$cellBlocks = array_merge(
								[
									$cellBlocks[0]
								],
								[
									"colspan=\"$colspanCount\""
								],
								array_slice( $cellBlocks, 1 )
							);
						}

						// Remove "###COLSPAN_<N>###" string from current cell block
						$cellBlocks[$cellBlockIndex] = str_replace(
							"###COLSPAN_$colspanCount###", "", $cellBlocks[$cellBlockIndex]
						);
					}
				}

				$line = implode( "|", $cellBlocks );

				// If we found "colspan" on current line -
				// then remove corresponding amount of next redundant empty lines (produced by Pandoc)
				if ( $colspanCount > 0 ) {
					for ( $i = $lineIndex + 1; $i < $lineIndex + $colspanCount; $i++ ) {
						unset( $lines[$i] );
					}
				}
			}
		}
		unset( $line );

		$text = implode( "\n", $lines );

		return $text;
	}
}