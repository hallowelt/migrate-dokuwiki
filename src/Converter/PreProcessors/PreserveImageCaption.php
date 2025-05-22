<?php

namespace HalloWelt\MigrateDokuwiki\Converter\PreProcessors;

use HalloWelt\MigrateDokuwiki\IProcessor;
use HalloWelt\MigrateDokuwiki\Utility\CategoryBuilder;

class PreserveImageCaption implements IProcessor {

	/**
	 * @param string $text
	 * @param string $path
	 * @return string
	 */
	public function process( string $text, string $path = '' ): string {
		$originalText = $text;

		$text = preg_replace_callback( '#(\{\{\s*)(.*?)(\s*\}\})#', static function ( $matches ) {
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

		if ( !is_string( $text ) ) {
			$category = CategoryBuilder::getPreservedMigrationCategory( 'Image caption failure' );
			$text = "{$originalText} {$category}";
		}

		return $text;
	}
}
