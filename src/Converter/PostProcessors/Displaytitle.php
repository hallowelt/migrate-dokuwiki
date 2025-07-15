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

		if ( count( $lines ) < 2 ) {
			return $text;
		}

		$hasDisplayTitle = false;
		$headingMatches = [];
		preg_match( '#(=+)\s*(.*?)\s*(=+)#', $lines[1], $headingMatches );
		if ( empty( $headingMatches[0] ) ) {
			return $text;
		}

		if ( isset( $headingMatches[2] ) && $headingMatches[2] !== '' ) {
			$heading = $headingMatches[2];
			$replacement = $this->makeReplacement( $heading );
			$text = str_replace( $headingMatches[0], $replacement, $text );
			$hasDisplayTitle = true;
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
