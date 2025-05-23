<?php

namespace HalloWelt\MigrateDokuwiki\Converter\PreProcessors;

use HalloWelt\MigrateDokuwiki\IProcessor;

class EnsureListIndention implements IProcessor {

	/**
	 * Pandoc interrupts lists and inserts parts of them into a
	 * pre-tag if the indentations of the lists do not correspond
	 * to the specifications, because there is whitespace before *.
	 * List elements contained in the pre-tag lose their indentation.
	 *
	 * https://www.dokuwiki.org/de:wiki:syntax#listen
	 *
	 * @param string $text
	 * @param string $path
	 * @return string
	 */
	public function process( string $text, string $path = '' ): string {
		$text = $this->fixAdditionalWhitespace( $text );
		return $text;
	}

	/**
	 * Remove whitespace if indention was made with 3 insead of 2 whitespaces
	 *
	 * @param string $text
	 * @return string
	 */
	private function fixAdditionalWhitespace( string $text ): string {
		$lines = explode( "\n", $text );

		$newLines = [];
		foreach ( $lines as $line ) {
			$newLine = preg_replace_callback(
				'#^(\s+)([\*,\-])(\s*)(.*)#',
				static function ( $matches ) {
					if ( $matches[3] === '' ) {
						// If there is no whitespace after the list indicator pandoc will create <pre> instead of <li>
						$matches[3] = ' ';
					}

					$indention = strlen( $matches[1] );
					if ( $indention % 2 === 1 ) {
						// Remove additional whitespace
						$indention = $indention % 2;
						$matches[1] = substr( $matches[1], 1 );
					}

					unset( $matches[0] );
					$matches = array_values( $matches );
					return implode( '', $matches );
				}, $line
			);
			if ( is_string( $newLine ) ) {
				$newLines[] = $newLine;
			} else {
				$newLines[] = $line;
			}
		}
		return implode( "\n", $newLines );
	}
}
