<?php

namespace HalloWelt\MigrateDokuwiki\Converter\PostProcessors;

use HalloWelt\MigrateDokuwiki\IProcessor;

class RestoreImageCaption implements IProcessor {

	/**
	 * @param string $text
	 * @param string $path
	 * @return string
	 */
	public function process( string $text, string $path = '' ): string {
		$text = preg_replace_callback( '/(#####PRESERVEIMAGECAPTIONSTART)(.*?)(#####)(.*?)(#####PRESERVEIMAGECAPTIONEND#####)/s', function( $matches ) {
			$caption = '';
			if ( trim( $matches[2] ) !== '' ) {
				$caption = trim( $matches[2] );
			} else {
				return $matches[4];
			}

			// Case: internal image ( [[File:a/b/c.png|c.png]] )
			if ( strpos( $matches[4], '[[' ) !== false ) {
				$inside = substr( $matches[4], 2, strlen( $matches[4] ) - 4 );
				$parts = explode( '|', $inside );
				$items = count( $parts );
				$parts[$items - 1] = $caption;
				return '[[' . implode( '|', $parts ) . ']]';
			}

			// Case: external image ( [http://example.com/test.png] )
			if ( strpos( $matches[4], '[' ) !== false ) {
				$inside = substr( $matches[4], 1, strlen( $matches[4] ) - 2 );
				return '[' . $inside . ' ' . $caption . ']';
			}

			return $matches[4];
		}, $text );

		return $text;
	}

}
