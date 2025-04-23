<?php

namespace HalloWelt\MigrateDokuwiki\Converter\PostProcessors;

use HalloWelt\MigrateDokuwiki\IProcessor;

class RestoreCode implements IProcessor {

	/**
	 * @param string $text
	 * @param string $path
	 * @return string
	 */
	public function process( string $text, string $path = '' ): string {
		$text = preg_replace_callback(
			'/(#####PRESERVECODESTART)(.*?)(#####)(.*?)(#####PRESERVECODEEND#####)/s', static function ( $matches ) {
				$lang = '';
				if ( trim( $matches[2] ) !== '' ) {
					$lang = ' lang="' . trim( $matches[2] ) . '"';
				}

				$code = base64_decode( $matches[4] );

				if ( strpos( $code, "\n" ) !== false ) {
					return "<syntaxhighlight{$lang}>{$code}</syntaxhighlight>";
				} elseif ( $lang !== '' ) {
					return "<syntaxhighlight{$lang}>{$code}</syntaxhighlight>";
				}
				return "<code>{$code}</code>";
			}, $text );

		return $text;
	}
}
