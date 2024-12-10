<?php

namespace HalloWelt\MigrateDokuwiki\Converter\PostProcessors\Table;

use HalloWelt\MigrateDokuwiki\IProcessor;
use HalloWelt\MigrateDokuwiki\Utility\MaskWikiLinksTrait;

class Colspan implements IProcessor {
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

		$this->lines = $this->maskInternalLinks( $this->lines );

		foreach ( $this->lines as $lineIndex => &$line ) {
			$colspanPos = strpos( $line, "###COLSPAN_" );
			if ( $colspanPos !== false ) {
				// Get amount of horizontally merged cells
				$colspanCount = 0;

				preg_match( "/###COLSPAN_(.*?)###/", $line, $matches );
				if ( isset( $matches[1] ) ) {
					$colspanCount = (int)$matches[1];
				}

				// Remove "###COLSPAN_<N>###" string from current line
				// We already got necessary information from this mask, do not need it anymore
				$line = str_replace( "###COLSPAN_$colspanCount###", "", $line );

				// In wikitext one line = one cell
				// One cell may have few blocks separated by "|"
				// If there is a block with HTML properties - it should be the first one
				$cellBlocks = explode( '|', $line );


				$isHeading = strpos( $line, '!' ) === 0;
				$isHeadingWithAttributes = $isHeading && count( $cellBlocks ) > 1;

				// If cell already contains block with HTML attributes (like "style" or "colspan")
				// Then just append "colspan" there
				if ( count( $cellBlocks ) > 2 || $isHeadingWithAttributes ) {
					$attributesBlockIndex = 1;
					if ( $isHeadingWithAttributes ) {
						$attributesBlockIndex = 0;
					}

					$cellBlocks[$attributesBlockIndex] = $cellBlocks[$attributesBlockIndex]
						. " colspan=\"$colspanCount\"";
				} else {
					if ( !$isHeading ) {
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
					} else {
						$cellBlocks[0] = '!' . "colspan=\"$colspanCount\"|" . substr( $cellBlocks[0], 1 ); ;
					}
				}

				$line = implode( "|", $cellBlocks );

				// If we found "colspan" on current line -
				// then remove corresponding amount (<colspan> - 1) of next redundant empty lines (produced by Pandoc)
				if ( $colspanCount > 0 ) {
					for ( $i = $lineIndex + 1; $i <= $lineIndex + ( $colspanCount - 1 ); $i++ ) {
						unset( $this->lines[$i] );
					}
				}
			}
		}
		unset( $line );

		$this->lines = $this->unmaskInternalLinks( $this->lines );

		$text = implode( "\n", $this->lines );

		return $text;
	}
}
