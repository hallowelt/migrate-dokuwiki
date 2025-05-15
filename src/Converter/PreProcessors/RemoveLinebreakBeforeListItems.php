<?php

namespace HalloWelt\MigrateDokuwiki\Converter\PreProcessors;

use HalloWelt\MigrateDokuwiki\IProcessor;

class RemoveLinebreakBeforeListItems implements IProcessor {

	/**
	 * Remove linebreak (\\) before list item
	 *
	 * @param string $text
	 * @param string $path
	 * @return string
	 */
	public function process( string $text, string $path = '' ): string {
		$lines = explode( "\n", $text );

		for ( $index = 1; $index < count( $lines ); $index++ ) {
			$line = $lines[$index];
			$lastLine = $lines[$index - 1];

			$regEx = '#^(\s+)([\*,\-])(\s*)(.*)#';

			$lineMatches = [];
			$statusLine = preg_match( $regEx, $line, $lineMatches );
			if ( $statusLine ) {
				// remove linebreak at end of list item
				if ( strpos( $line, '\\' ) === strlen( $line ) - 2 ) {
					$lines[$index] = substr( $line, 0, strlen( $line ) - 2 );
				}
			}

			// remove linebreak befor list
			if ( strpos( $lastLine, '\\' ) === strlen( $lastLine ) - 2 ) {
				$lines[$index - 1] = substr( $lastLine, 0, strlen( $lastLine ) - 2 );
			}
		}

		$text = implode( "\n", $lines );

		return $text;
	}
}
