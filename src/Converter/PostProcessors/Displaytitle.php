<?php

namespace HalloWelt\MigrateDokuwiki\Converter\PostProcessors;

use HalloWelt\MigrateDokuwiki\IProcessor;
use HalloWelt\MigrateDokuwiki\Utility\CategoryBuilder;

class Displaytitle implements IProcessor {

	/**
	 * @param string $text
	 * @param string $path
	 * @return string
	 */
	public function process( string $text, string $path = '' ): string {
		$lines = explode( "\n", $text );

		$hasDisplayTitle = false;
		$idMatches = [];
		preg_match( '#<span id="(.*?)"\s*></span>#', $lines[0], $idMatches );
		if ( empty( $idMatches ) ) {
			return $text;
		}
		$id = $idMatches[1];
		$id = str_replace( '-', ' ', $id );

		$headingMatches = [];
		preg_match_all( '#(=+)\s*(.*?)\s*(=+)#', $lines[1], $headingMatches );
		if ( empty( $headingMatches[0] ) ) {
			$category = CategoryBuilder::getPreservedMigrationCategory( 'Displaytitle not set' );
			$text .= " {$category}";
			return $text;
		}

		for ( $index = 0; $index < count( $headingMatches[0] ); $index++ ) {
			$heading = $headingMatches[2][$index];
			$replacement = $this->makeReplacement( $heading );

			if ( strtolower( str_replace( '-', ' ', $heading ) ) === strtolower( $id ) ) {
				$text = str_replace( $headingMatches[0][$index], $replacement, $text );
				$hasDisplayTitle = true;
				break;
			}
		}

		if ( $hasDisplayTitle ) {
			$category = CategoryBuilder::getPreservedMigrationCategory( 'Displaytitle set' );
			$text .= " {$category}";
		}
		return $text;
	}

	/**
	 * @param string $heading
	 * @return string
	 */
	private function makeReplacement( string $heading ): string {
		return "{{DISPLAYTITLE:$heading}}";
	}
}
