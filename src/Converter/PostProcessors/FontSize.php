<?php

namespace HalloWelt\MigrateDokuwiki\Converter\PostProcessors;

use HalloWelt\MigrateDokuwiki\IProcessor;
use HalloWelt\MigrateDokuwiki\Utility\CategoryBuilder;

class FontSize implements IProcessor {

	/**
	 * @param string $text
	 * @param string $path
	 * @return string
	 */
	public function process( string $text, string $path = '' ): string {
		$originalText = $text;

		// replace <fs ...>...</fs>
		$regEx = '#(<|&lt;)fs(.*?)(>|&gt;)(.*?)(<|&lt;)/fs(>|&gt;)#s';
		$text = preg_replace_callback( $regEx, static function ( $matches ) {
			$fontsize = $matches[2];
			$text = $matches[4];
			$replacement = '<span class="font-size ' . trim( $fontsize ) . '">' . $text . '</span>';

			return $replacement;
		}, $text );

		if ( !is_string( $text ) ) {
			$category = CategoryBuilder::getPreservedMigrationCategory( 'Fontsize failure' );
			$text = "{$originalText} {$category}";
		}

		return $text;
	}
}
