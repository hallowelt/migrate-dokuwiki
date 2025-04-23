<?php

namespace HalloWelt\MigrateDokuwiki\Converter\PreProcessors;

use HalloWelt\MigrateDokuwiki\IProcessor;

class PreserveCode implements IProcessor {

	/**
	 * @param string $text
	 * @param string $path
	 * @return string
	 */
	public function process( string $text, string $path = '' ): string {
		$processedText = preg_replace_callback( '#(<code)(.*?)(>)(.*?)(</code>)#s', static function ( $matches ) {
			$lang = $matches[2];
			$code = base64_encode( $matches[4] );

			return "#####PRESERVECODESTART{$lang}#####{$code}#####PRESERVECODEEND#####";
		}, $text );

		if ( is_string( $processedText ) ) {
			return $processedText;
		}
		return $text;
	}
}
