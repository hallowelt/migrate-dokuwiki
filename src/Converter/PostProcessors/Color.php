<?php

namespace HalloWelt\MigrateDokuwiki\Converter\PostProcessors;

use HalloWelt\MigrateDokuwiki\IProcessor;
use HalloWelt\MigrateDokuwiki\Utility\CategoryBuilder;

class Color implements IProcessor {

	/**
	 * @param string $text
	 * @param string $path
	 * @return string
	 */
	public function process( string $text, string $path = '' ): string {
		$originalText = $text;

		// replace <color ...>
		$regEx = '#(<|&lt;)color(.*?)(>|&gt;)(.*?)(<|&lt;)/color(>|&gt;)#s';
		$text = preg_replace_callback( $regEx, static function ( $matches ) {
			$color = $matches[2];
			$text = $matches[4];
			$replacement = '<span style="color: ' . trim( $color ) . '">' . $text . '</span>';
			return $replacement;
		}, $text );

		if ( !is_string( $text ) ) {
			$category = CategoryBuilder::getPreservedMigrationCategory( 'Color failure' );
			$text = "{$originalText} {$category}";
		}

		return $text;
	}
}
