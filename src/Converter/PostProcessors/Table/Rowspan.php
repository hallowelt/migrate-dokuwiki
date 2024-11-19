<?php

namespace HalloWelt\MigrateDokuwiki\Converter\PostProcessors\Table;

use HalloWelt\MigrateDokuwiki\IProcessor;

class Rowspan implements IProcessor {

	/**
	 * @param string $text
	 * @return string
	 */
	public function process( string $text ): string {
		$lines = explode( "\n", $text );

		$isTable = false;

		$rowIndex = 0;
		$colIndex = 0;

		$linesToDelete = [];
		$rowspanCounts = [];

		// We'll use this mapping later to change only lines which we need,
		// Which contain "rowspan" cells
		// Without need to process all table cells once more
		$cellCoordsToLineMap = [];

		foreach ( $lines as $lineIndex => &$line ) {
			$line = trim( $line );

			if ( strpos( $line, '{|' ) === 0 ) {
				// If we found "{|" - then we start processing the table
				$isTable = true;

				$rowIndex = 0;
				$colIndex = 0;

				$rowspanCounts = [];

				// We use separate "cell -> line" mapping for each table we process
				$cellCoordsToLineMap = [];

				continue;
			}

			if ( strpos( $line, '|}' ) === 0 ) {
				$isTable = false;

				// If we got finished with current table - set proper "rowspan" attributes
				// for specific cells in this table
				foreach ( $rowspanCounts as $rowIndex => $rowspanCells ) {
					foreach ( $rowspanCells as $colIndex => $rowspanCount ) {
						$tableCellLineIndex = $cellCoordsToLineMap[$rowIndex][$colIndex];

						// We found necessary line in the wikitext
						// Now parse this table line and add necessary "rowspan" attribute value
						$rowspanLine = $lines[$tableCellLineIndex];

						$cellBlocks = explode( '|', $rowspanLine );

						// If cell already contains block with HTML attributes (like "style" or "colspan")
						// Then just append "colspan" there
						if ( count( $cellBlocks ) > 2 ) {
							$cellBlocks[1] = $cellBlocks[1] . " rowspan=\"$rowspanCount\"";
						} else {
							// Otherwise add such block
							$cellBlocks = array_merge(
								[
									$cellBlocks[0]
								],
								[
									"rowspan=\"$rowspanCount\""
								],
								array_slice( $cellBlocks, 1 )
							);
						}

						$lines[$tableCellLineIndex] = implode( '|', $cellBlocks );
					}
				}

				continue;
			}

			if ( $isTable ) {
				// Row separator
				if ( strpos( $line, '|-' ) === 0 ) {
					$rowIndex++;
					$colIndex = 0;

					continue;
				}

				$colIndex++;

				// It makes sense to record lines indexes only for "cell content lines"
				$cellCoordsToLineMap[$rowIndex][$colIndex] = $lineIndex;

				// Check if ":::" is the only content of current cell
				$cellBlocks = explode( '|', $line );
				foreach ( $cellBlocks as $cellBlock ) {
					$cellBlockTrimmed = trim( $cellBlock );

					if ( $cellBlockTrimmed === ':::' ) {
						$linesToDelete[] = $lineIndex;

						// Mark that cell for removal
						$rowspanCounts[$rowIndex][$colIndex] = -1;

						$rowspanRow = $rowIndex - 1;

						// There can be any amount of cells united in one vertically using "rowspan"
						// And in that case in DokuWiki there will be corresponding amount
						// of vertical aligned cells filled with ":::"
						while ( true ) {
							// If that cell above also contains ":::" - go one more row above
							if (
								isset( $rowspanCounts[$rowspanRow][$colIndex] ) &&
								$rowspanCounts[$rowspanRow][$colIndex] === -1
							) {
								$rowspanRow--;
							} else {
								break;
							}
						}

						if ( !isset( $rowspanCounts[$rowspanRow][$colIndex] ) ) {
							// Minimum amount for "rowspan" attribute is 2
							$rowspanCounts[$rowspanRow][$colIndex] = 2;
						} else {
							$rowspanCounts[$rowspanRow][$colIndex]++;
						}
					}
				}
			}
		}
		unset( $line );

		// Remove all cells containing only ":::"
		foreach ( $linesToDelete as $lineToDelete ) {
			unset( $lines[$lineToDelete] );
		}

		$text = implode( "\n", $lines );

		return $text;
	}
}
