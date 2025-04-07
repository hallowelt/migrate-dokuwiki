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
			$matches[1] = '#####PRESERVECODESTART';
			$matches[3] = '#####';

			$code = $matches[4];
			$code = str_replace( "\n", '#####PRESERVECODELINEBREAKE#####', $code );
			$code = str_replace( '"', '#####PRESERVECODEDOUBLEQOUTE#####', $code );
			$matches[4] = $code;

			$matches[5] = '#####PRESERVECODEEND#####';

			unset( $matches[0] );
			return implode( '', $matches );
		}, $text );

		if ( is_string( $processedText ) ) {
			return $processedText;
		}
		return $text;
	}
}
