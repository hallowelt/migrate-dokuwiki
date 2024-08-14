<?php

namespace HalloWelt\MigrateDokuwiki\Converter\PostProcessors;

use HalloWelt\MigrateDokuwiki\IProcessor;

class Image implements IProcessor {

	/**
	 * @param string $text
	 * @return string
	 */
	public function process( string $text ): string {
		// remove leading / which is placed by pandoc between File: and the file title
		$regEx = '#(\[\[File:)(/)(.*?)(\]\])#';
		$text = preg_replace_callback( $regEx, static function ( $matches ) {
			unset( $matches[0] );
			unset( $matches[2] );
			$replacement = implode( '', $matches );
			return $replacement;
		}, $text );
		return $text;
	}
}
