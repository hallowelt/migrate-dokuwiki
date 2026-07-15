<?php

namespace HalloWelt\MigrateDokuwiki\Converter\PreProcessors;

use HalloWelt\MigrateDokuwiki\IProcessor;

class AddLinebreakBevoreHeading implements IProcessor {

	/**
	 * If there is no linebreak before a headline pandoc will not add a jumpmark.
	 * Exception: First headline in text.
	 *
	 * @param string $text
	 * @param string $path
	 * @return string
	 */
	public function process( string $text, string $path = '' ): string {
		$lines = explode( "\n", $text );
		$output = [];

		for ( $index = 0; $index < count( $lines ); $index++ ) {
			$line = $lines[$index];

			if ( $index === 0 ) {
				$output[] = $line;
				continue;
			}

			$regEx = '#^(=+)(.*?)(\1)(.*)#';

			$matches = [];
			$statusLine = preg_match( $regEx, $line, $matches );

			if ( empty( $matches ) ) {
				$output[] = $line;
				continue;
			}

			if ( $lines[ $index - 1 ] !== '' ) {
				$output[] = '';
			}

			$output[] = $line;

		}

		$text = implode( "\n", $output );

		return $text;
	}
}
