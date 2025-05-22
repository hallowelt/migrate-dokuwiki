<?php

namespace HalloWelt\MigrateDokuwiki\Converter\PostProcessors;

use HalloWelt\MigrateDokuwiki\IProcessor;
use HalloWelt\MigrateDokuwiki\Utility\CategoryBuilder;

class RestoreCode implements IProcessor {

	/**
	 * @param string $text
	 * @param string $path
	 * @return string
	 */
	public function process( string $text, string $path = '' ): string {
		$originalText = $text;

		$text = preg_replace_callback(
			'/(#####PRESERVECODESTART)(.*?)(#####)(.*?)(#####PRESERVECODEEND#####)/s', static function ( $matches ) {
				$lang = '';
				if ( trim( $matches[2] ) !== '' ) {
					$lang = ' lang="' . trim( $matches[2] ) . '"';
				}

				$code = base64_decode( $matches[4] );

				if ( strpos( $code, "\n" ) !== false && $lang !== '' ) {
					return "<syntaxhighlight{$lang}>{$code}</syntaxhighlight>";
				} elseif ( strpos( $code, "\n" ) !== false ) {
					return "<pre>{$code}</pre>";
				} elseif ( $lang !== '' ) {
					return "<syntaxhighlight{$lang}>{$code}</syntaxhighlight>";
				}
				return "<code>{$code}</code>";
			}, $text );

		if ( !is_string( $text ) ) {
			$category = CategoryBuilder::getPreservedMigrationCategory( 'Code failure' );
			$text = "{$originalText} {$category}";
		}

		return $text;
	}
}
