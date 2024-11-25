<?php

namespace HalloWelt\MigrateDokuwiki\Converter\PostProcessors;

use HalloWelt\MigrateDokuwiki\IProcessor;

class ListItems implements IProcessor {

	/**
	 * @param string $text
	 * @return string
	 */
	public function process( string $text ): string {
		$output = [];
		$lines = explode( "\n", $text );
		foreach ( $lines as $line ) {
			$posStar = strpos( $line, '*' );
			$posHash = strpos( $line, '#' );

			if ( $posStar !== false && $posStar === 0 ) {
				$this->splitList( '*', $line, $output );
			} else if ( $posHash !== false && $posHash === 0 ) {
				$this->splitList( '#', $line, $output );
			} else {
				$output[] = $line;
			}
		}

		$text = implode( "\n", $output );
		return $text;
	}

	private function splitList( string $separator, string $line, array &$output ): void {
		$listLines = explode( $separator, $line );
		array_shift( $listLines ); // get rid of first empty element

		$newLine = '';
		foreach ( $listLines as $listLine ) {
			if ( strlen( $listLine ) === 0 ) {
				$newLine .= $separator;
				continue;
			}
			$listLine = trim( $listLine );
			$newLine .= "$separator $listLine";
			$output[] = $newLine;
			$newLine = '';
		}
	}
}
