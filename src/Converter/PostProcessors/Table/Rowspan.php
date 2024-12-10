<?php

namespace HalloWelt\MigrateDokuwiki\Converter\PostProcessors\Table;

use HalloWelt\MigrateDokuwiki\IProcessor;
use HalloWelt\MigrateDokuwiki\Utility\MaskWikiLinksTrait;

/**
 * IMPORTANT!
 *
 * This postprocessor should only be used after this one:
 * {@link \HalloWelt\MigrateDokuwiki\Converter\PostProcessors\Table\Colspan}
 */
class Rowspan implements IProcessor {
	use MaskWikiLinksTrait;

	/**
	 * @var array
	 */
	private $lines = [];

	/**
	 * @param string $text
	 * @return string
	 */
	public function process( string $text ): string {
		$this->lines = explode( "\n", $text );

		// As soon as we heavily rely on "|" as part of tables syntax
		// It makes sense to "mask" internal links before start processing
		// Internal links use "|" as separator, so it may make harder table cells recognizing
		$this->lines = $this->maskInternalLinks( $this->lines );

		$isTable = false;

		$rowIndex = 0;
		$colIndex = 0;

		$linesToDelete = [];
		$rowspanCounts = [];

		// We'll use this mapping later to change only lines which we need,
		// Which contain "rowspan" cells
		// Without need to process all table cells once more
		$cellCoordsToLineMap = [];

		foreach ( $this->lines as $lineIndex => &$line ) {
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

				// Look for maximum column index in the table.
				// It will later be used to prevent a bug.
				// Problem is that when cell already has "colspan=<maxColIndex>",
				// and you add "rowspan=..." - it never works normally.
				// For example, some extra empty columns/rows may appear.
				$maxColAmount = 1;
				foreach ( $cellCoordsToLineMap as $rowIndex => $cols ) {
					foreach ( $cols as $columnIndex => $lineIndex ) {
						if ( $columnIndex > $maxColAmount ) {
							$maxColAmount = $columnIndex;
						}
					}
				}

				// If we got finished with current table - set proper "rowspan" attributes
				// for specific cells in this table
				foreach ( $rowspanCounts as $rowIndex => $rowspanCells ) {
					foreach ( $rowspanCells as $colIndex => $rowspanCount ) {
						// In case with multi-line cells we anyway need only the first line,
						// because it should contain cell attributes line "rowspan" or "colspan"
						$tableCellLineIndex = $cellCoordsToLineMap[$rowIndex][$colIndex][0];

						// We found necessary line in the wikitext
						// Now parse this table line and add necessary "rowspan" attribute value
						$rowspanLine = $this->lines[$tableCellLineIndex];

						$cellBlocks = explode( '|', $rowspanLine );

						$isHeadingWithAttributes = false;
						if ( strpos( $rowspanLine, '!' ) === 0 && count( $cellBlocks ) > 1 ) {
							$isHeadingWithAttributes = true;
						}

						// If cell already contains block with HTML attributes (like "style" or "colspan")
						// Then just append "rowspan" there
						if ( count( $cellBlocks ) > 2 || $isHeadingWithAttributes ) {
							$attributesBlockIndex = 1;
							if ( $isHeadingWithAttributes ) {
								$attributesBlockIndex = 0;
							}

							// Check if cell already contains "colspan"
							// If it contains and value equals maximum column index - do not add "rowspan"
							preg_match( "/colspan=\"(.*?)\"/", $cellBlocks[$attributesBlockIndex], $matches );

							if ( isset( $matches[1] ) ) {
								$colspanCount = (int)$matches[1];

								if ( $colspanCount === $maxColAmount ) {
									continue;
								}
							}

							$cellBlocks[$attributesBlockIndex] = $cellBlocks[$attributesBlockIndex]
								. " rowspan=\"$rowspanCount\"";
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

						$this->lines[$tableCellLineIndex] = implode( '|', $cellBlocks );
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

				// New cell begins only with "|" symbol, or with "!" in case of heading
				// If we are processing table and line does not start with "|" or "!" -
				// - then it's just multi-line cell. Consider it as the same column
				if (
					(
						strpos( $line, '|' ) === 0 ||
						strpos( $line, '!' ) === 0
					)
					// If line starts with "|+" - that a caption
					// Should not increment column index
					&& strpos( $line, '+' ) !== 1
				) {
					$colIndex++;
				}

				// It makes sense to record lines indexes only for "cell content lines"
				// Also consider multi-line cells.
				if ( !isset( $cellCoordsToLineMap[$rowIndex][$colIndex] ) ) {
					$cellCoordsToLineMap[$rowIndex][$colIndex] = [ $lineIndex ];
				} else {
					// So if we process new wikitext line, but column index is the same -
					// - then that's the same cell
					$cellCoordsToLineMap[$rowIndex][$colIndex][] = $lineIndex;
				}

				// Check if ":::" is the only content of current cell
				$cellBlocks = explode( '|', $line );
				foreach ( $cellBlocks as $cellBlock ) {
					$cellBlockTrimmed = trim( $cellBlock );

					if ( $cellBlockTrimmed === ':::' ) {
						// Mark for removal not only current line,
						// but all lines associated with that specific table cell,
						// considering also multi-line cells
						$linesToDelete = array_merge( $linesToDelete, $cellCoordsToLineMap[$rowIndex][$colIndex] );

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

						// There can be only one reason why we do not have line mapped to table cell -
						// - this table cell is merged with previous one using "colspan".
						// So one cell takes two or more columns.
						// Nothing to do here in that case, can be safely ignored.
						if ( !isset( $cellCoordsToLineMap[$rowspanRow][$colIndex] ) ) {
							break;
						}

						if ( !isset( $rowspanCounts[$rowspanRow][$colIndex] ) ) {
							// Minimum amount for "rowspan" attribute is 2
							$rowspanCounts[$rowspanRow][$colIndex] = 2;
						} else {
							$rowspanCounts[$rowspanRow][$colIndex]++;
						}
					}
				}

				// If table cell contains "colspan" - then increase column index on corresponding amount
				// Because this cell takes several columns
				$matches = [];
				preg_match( "/colspan=\"(.*?)\"/", $line, $matches );

				if ( isset( $matches[1] ) ) {
					$colspanCount = (int)$matches[1];

					// Consider that we already incremented column index
					$colIndex += ( $colspanCount - 1 );
				}
			}
		}
		unset( $line );

		// Remove all cells containing only ":::"
		foreach ( $linesToDelete as $lineToDelete ) {
			unset( $this->lines[$lineToDelete] );
		}

		$this->lines = $this->unmaskInternalLinks( $this->lines );

		$text = implode( "\n", $this->lines );

		return $text;
	}
}
