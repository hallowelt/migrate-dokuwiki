<?php

namespace HalloWelt\MigrateDokuwiki\Converter\PostProcessors;

use HalloWelt\MigrateDokuwiki\IProcessor;

class AddLinebreakAfterHeading implements IProcessor {

	/**
	 * Add linebreak after heading if there is none.
	 * Pandoc sometimes removes this linebreak.
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

			$regEx = '#^(=+)(.*?)(\1)(.*)$#';

			$matches = [];
			$statusLine = preg_match( $regEx, $line, $matches );

			if ( empty( $matches ) || $matches[2] === '' ) {
				$output[] = $line;
				continue;
			}

			$output[] = "{$matches[1]}{$matches[2]}{$matches[3]}";

			if ( trim( $matches[4] ) !== '' ) {
				$output[] = trim( $matches[4] );
			}

		}

		$text = implode( "\n", $output );

		return $text;
	}
}
