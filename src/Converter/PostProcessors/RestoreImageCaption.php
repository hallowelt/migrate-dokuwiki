<?php

namespace HalloWelt\MigrateDokuwiki\Converter\PostProcessors;

use HalloWelt\MigrateDokuwiki\IProcessor;

class RestoreImageCaption implements IProcessor {

	/**
	 * This post processor has to run before Image post processor
	 *
	 * @param string $text
	 * @param string $path
	 * @return string
	 */
	public function process( string $text, string $path = '' ): string {
		$text = preg_replace_callback(
			'/(#####PRESERVEIMAGECAPTIONSTART)(.*?)(#####)(.*?)(#####PRESERVEIMAGECAPTIONEND#####)/s',
			static function ( $matches ) {
				$caption = '';
				if ( trim( $matches[2] ) !== '' ) {
					$caption = trim( $matches[2] );
				} else {
					return $matches[4];
				}

			// Case: internal image ( [[File:a/b/c.png|c.png]] )
				if ( strpos( $matches[4], '[[' ) !== false ) {
					$inside = trim( $matches[4], '[]' );
					$parts = explode( '|', $inside );
					$items = count( $parts );
					$parts[$items - 1] = $caption;
					return '[[' . implode( '|', $parts ) . ']]';
				}

			// Case: external image ( [http://example.com/test.png] or http://example.com/test.png )
				if ( strpos( $matches[4], '[' ) !== false ) {
					$inside = trim( $matches[4], '[]' );
					return '[' . $inside . ' ' . $caption . ']';
				} else {
					return '[' . $matches[4] . ' ' . $caption . ']';
				}

				return $matches[4];
			},
			$text
		);

		return $text;
	}

}
