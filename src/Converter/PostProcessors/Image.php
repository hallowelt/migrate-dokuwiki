<?php

namespace HalloWelt\MigrateDokuwiki\Converter\PostProcessors;

use HalloWelt\MigrateDokuwiki\IProcessor;

class Image implements IProcessor {

	/** @var array */
	private $advancedConfig = [];

	/**
	 * @param array $advancedConfig
	 */
	public function __construct( array $advancedConfig = [] ) {
		$this->advancedConfig = $advancedConfig;
	}

	/**
	 * @param string $text
	 * @param string $path
	 * @return string
	 */
	public function process( string $text, string $path = '' ): string {
		// remove leading / which is placed by pandoc between File: and the file title
		$text = $this->removeLeadingSlash( $text );
		$text = $this->addFileTitle( $text );
		$text = $this->fixExternalFileLinks( $text );
		if ( isset( $this->advancedConfig['ext-ns-file-repo-compat'] )
			&& $this->advancedConfig['ext-ns-file-repo-compat'] === true
		) {
			$text = $this->restoreNamespace( $text );
		}
		if ( isset( $this->advancedConfig['media-link-extensions'] )
			&& !empty( $this->advancedConfig['media-link-extensions'] )
		) {
			$text = $this->convertToMediaLink( $text, $this->advancedConfig['media-link-extensions'] );
		}
		$text = $this->fixAlignment( $text );
		return $text;
	}

	/**
	 * remove leading "/" which is placed by pandoc between File: and the file title
	 *
	 * @param string $text
	 * @return string
	 */
	private function removeLeadingSlash( string $text ): string {
		$regEx = '#(\[\[File:)(/)(.*?)(\]\])#';
		$text = preg_replace_callback( $regEx, static function ( $matches ) {
			unset( $matches[0] );
			unset( $matches[2] );
			$matches = array_values( $matches );
			$replacement = implode( '', $matches );
			return $replacement;
		}, $text );
		return $text;
	}

	/**
	 * Add file title to files with tailing "|" which sometimes appears in the wikitext
	 *
	 * @param string $text
	 * @return string
	 */
	private function addFileTitle( string $text ): string {
		$regEx = '#(\[\[File:)(.*?)(|)(\]\])#';
		$text = preg_replace_callback( $regEx, static function ( $matches ) {
			$pipePos = strpos( $matches[2], '|' );
			if ( $pipePos === false ) {
				return $matches[0];
			}

			$caption = substr( $matches[2], 0, $pipePos );
			$slashPos = strrpos( $caption, '/' );
			if ( $slashPos !== false ) {
				$caption = substr( $caption, $slashPos + 1 );
			}

			$matches[2] = $matches[2] . $caption;

			unset( $matches[0] );
			$matches = array_values( $matches );
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
				if ( $hasSizeMatch === 1 ) {
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

	/**
	 * @param string $text
	 * @return string
	 */
	private function restoreNamespace( string $text ): string {
		$regEx = '#(\[\[File:)(.*?)(\]\])#';
		$text = preg_replace_callback( $regEx, static function ( $matches ) {
			$target = $matches[2];
			$target = str_replace( '#####preserveimagenamespace#####', ':', $target );

			return $matches[1] . $target . $matches[3];
		},
		$text );

		return $text;
	}

	/**
	 * @param string $text
	 * @param array $extensions
	 * @return string
	 */
	private function convertToMediaLink( string $text, array $extensions ): string {
		$regEx = '#(\[\[File:)(.*?)(\]\])#';
		$text = preg_replace_callback( $regEx, static function ( $matches ) use ( $extensions ) {
			$data = $matches[2];
			$pipePos = strpos( $data, '|' );
			$target = '';
			$params = [];
			if ( $pipePos !== false ) {
				$target = substr( $data, 0, $pipePos );
				$param = substr( $data, $pipePos );
				$params = explode( '|', $param );
			}

			$extensionPos = strrpos( $target, '.' );
			if ( $extensionPos === false ) {
				return $matches[0];
			}

			$extension = substr( $target, $extensionPos );
			if ( in_array( $extension, $extensions ) ) {
				// After addFileTitle each file should have a caption
				$caption = array_pop( $params );
				return "[[Media:{$matches[2]}|{$caption}]]";
			}

			return $matches[0];
		},
		$text );

		return $text;
	}
}
