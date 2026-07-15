<?php

namespace HalloWelt\MigrateDokuwiki\Converter\PreProcessors;

use HalloWelt\MigrateDokuwiki\IProcessor;

class ConvertArrowInHeading implements IProcessor {

	/**
	 * In Dokuwiki it is possible to use a arrow (=>) in heading.
	 * This will break some processors and wiki syntax.
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

			$line = str_replace( [ '=>', '<=' ], [ '->', '<-' ], $line );

			$regEx = '#^(=+)(.*?)(\1)(.*)#';

			$matches = [];
			$statusLine = preg_match( $regEx, $line, $matches );

			if ( empty( $matches ) ) {
				$output[] = $lines[$index];
				continue;
			}

			$output[] = $line;

		}

		$text = implode( "\n", $output );

		return $text;
	}
}
