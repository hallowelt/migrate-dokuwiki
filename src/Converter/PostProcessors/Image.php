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
		$text = $this->removeLeadingSlash( $text );
		$text = $this->fixExternalFileLinks( $text );
		return $text;
	}

	/**
	 * remove leading / which is placed by pandoc between File: and the file title
	 * 
	 * @param string $text
	 * @return string
	 */
	private function removeLeadingSlash( string $text ): string {
		$regEx = '#(\[\[File:)(/)(.*?)(\]\])#';
		$text = preg_replace_callback( $regEx, static function ( $matches ) {
			unset( $matches[0] );
			unset( $matches[2] );
			$replacement = implode( '', $matches );
			return $replacement;
		}, $text );
		return $text;
	}

	/** 
	 * [[File:https://upload.wikimedia.org/wikipedia/mediawiki/thumb/a/a9/Example.jpg/330px-Example.jpg|Image from external source]]
	 * https://www.mediawiki.org/wiki/Manual:$wgAllowExternalImagesFrom
	 * 
	 * @param string $text
	 * @return string
	 */
	private function fixExternalFileLinks( string $text ): string {
		$regEx = '#(\[\[File:)(.*?)(\]\])#';
		$text = preg_replace_callback( $regEx, static function ( $matches ) {
			$target = $matches[2];
			$pipePos = strpos( $target, '|' );
			if ( $pipePos !== false ) {
				$target = substr( $target, 0, $pipePos );
			}

			$parsedUrl = parse_url( $target );
			if ( !isset( $parsedUrl['scheme'] ) || !in_array( $parsedUrl['scheme'], [ 'http', 'https'] ) ) {
				return $matches[0];
			}
			
			$replacement = $target;
			return $replacement;
		}, $text );

		return $text;
	}
}
