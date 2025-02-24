<?php

namespace HalloWelt\MigrateDokuwiki\Converter\PreProcessors;

use HalloWelt\MigrateDokuwiki\IProcessor;

class PreserveImageCaption implements IProcessor {

	/**
	 * @param string $text
	 * @param string $path
	 * @return string
	 */
	public function process( string $text, string $path = '' ): string {
		$text = preg_replace_callback( '#(\{\{\s*)(.*?)(\s*\}\})#', function( $matches ) {
			$paramStart = strpos( $matches[2], '|' );
			if ( $paramStart === false ) {
				// no caption
				return $matches[0];
			}

			$target = substr( $matches[2], 0, $paramStart );
			$caption = substr( $matches[2], $paramStart + 1 );
			$start = '#####PRESERVEIMAGECAPTIONSTART ' . trim( $caption ) . '#####';
			$end = '#####PRESERVEIMAGECAPTIONEND#####';

			$image = $matches[1] . $target . $matches[3];

			return $start . $image . $end;
		}, $text );

		return $text;
	}
}
