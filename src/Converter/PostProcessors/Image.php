<?php

namespace HalloWelt\MigrateDokuwiki\Converter\PostProcessors;

use HalloWelt\MigrateDokuwiki\IProcessor;

class Image implements IProcessor {

	/**
	 * @param string $text
	 * @param string $path
	 * @return string
	 */
	public function process( string $text, string $path = '' ): string {
		// remove leading / which is placed by pandoc between File: and the file title
		$text = $this->removeLeadingSlash( $text );
		$text = $this->fixExternalFileLinks( $text );
		$text = $this->fixAlignment( $text );
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
	 * [[File:https://<wiki url>/thumb/a/a9/Example.jpg/330px-Example.jpg|Image from external source]]
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
			if ( !isset( $parsedUrl['scheme'] ) || !in_array( $parsedUrl['scheme'], [ 'http', 'https' ] ) ) {
				return $matches[0];
			}

			$replacement = "[$target]";
			return $replacement;
		}, $text );

		return $text;
	}

	/**
	 * @param string $text
	 * @return string
	 */
	private function fixAlignment( string $text ): string {
		$regEx = '#(\[\[File:)(.*?)(\]\])#';
		$text = preg_replace_callback( $regEx, static function ( $matches ) {
			$inside = $matches[2];
			$parts = explode( '|', $inside );
			$target = $parts[0];
			$align = '';
			$size = '';
			$caption = '';

			for ( $index = 1; $index < count( $parts ); $index++ ) {
				$part = $parts[$index];
				// alignment
				if ( $part === 'class=align-left' ) {
					$align = '|left';
					continue;
				}
				if ( $part === 'class=align-right' ) {
					$align = '|right';
					continue;
				}
				// size
				$sizeMatches = [];
				$hasSizeMatch = preg_match( '#(\d+|x\d+|\d+x\d+)px#', $part, $sizeMatches );
				var_dump( $hasSizeMatch );
				var_dump( $sizeMatches );
				if ( $hasSizeMatch === 1 ) {
					var_dump( __LINE__ );
					$size = '|' . $sizeMatches[0];
					continue;
				}
				// caption
				$caption = '|' . $part;
			}

			return $matches[1] . $target . $align . $size . $caption . $matches[3];
		}, $text );

		return $text;
	}
}
