<?php

namespace HalloWelt\MigrateDokuwiki\Converter\PostProcessors;

use HalloWelt\MigrateDokuwiki\IProcessor;
use HalloWelt\MigrateDokuwiki\Utility\CategoryBuilder;

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
		$text = $this->fixExternalFileLinks( $text );
		$text = $this->markBrokenFileTarget( $text );
		$text = $this->addFileCaption( $text );
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
	 * Mark file links with broken file target
	 *
	 * @param string $text
	 * @return string
	 */
	private function addFileCaption( string $text ): string {
		$advancedConfig = $this->advancedConfig;
		$regEx = '#(\[\[File:)(.*?)(\]\])#';
		$text = preg_replace_callback( $regEx, static function ( $matches ) use ( $advancedConfig ) {
			$pipePos = strpos( $matches[2], '|' );
			if ( $pipePos === false ) {
				return $matches[0];
			}

			if ( $pipePos !== strlen( $matches[2] ) - 1 ) {
				return $matches[0];
			}

			$caption = substr( $matches[2], 0, $pipePos );
			$slashPos = strrpos( $caption, '/' );
			if ( $slashPos !== false ) {
				$caption = substr( $caption, $slashPos + 1 );
			}

			if ( isset( $advancedConfig['ext-ns-file-repo-compat'] )
				&& $advancedConfig['ext-ns-file-repo-compat'] === true
			) {

				$namespacePos = strpos( $caption, ':' );
				$preserveMarker = '#####preserveimagenamespace#####';
				$preserveNamespacePos = strpos( $caption, $preserveMarker );
				if ( $namespacePos !== false ) {
					$caption = substr( $caption, $namespacePos + 1 );
				} elseif ( $preserveNamespacePos !== false ) {
					$caption = substr( $caption, $preserveNamespacePos + strlen( $preserveMarker ) );
				}
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
	 * Add file title to files with tailing "|" which sometimes appears in the wikitext
	 *
	 * @param string $text
	 * @return string
	 */
	private function markBrokenFileTarget( string $text ): string {
		$regEx = '#(\[\[File:)(.*?)(\]\])#';
		$text = preg_replace_callback( $regEx, static function ( $matches ) {
			$pipePos = strpos( $matches[2], '|' );
			if ( $pipePos === false ) {
				return $matches[0];
			}

			if ( $pipePos !== 0 ) {
				return $matches[0];
			}

			$category = CategoryBuilder::getMigrationCategory( 'Broken file target' );
			return "{$matches[0]}{$category}";
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
			$label = '';
			$pipePos = strpos( $target, '|' );
			if ( $pipePos !== false ) {
				$target = substr( $matches[2], 0, $pipePos );
				$label = substr( $matches[2], $pipePos + 1 );
			}

			$parsedUrl = parse_url( $target );
			if ( !isset( $parsedUrl['scheme'] ) || !in_array( $parsedUrl['scheme'], [ 'http', 'https' ] ) ) {
				return $matches[0];
			}

			if ( $label === '' ) {
				$replacement = "<span class=\"external-image\">[$target]</span>";
			} else {
				$replacement = "<span class=\"external-image\">[$target $label]</span>";
			}
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
			$caption = '';

			if ( $pipePos !== false && $pipePos !== 0 ) {
				$target = substr( $data, 0, $pipePos );
				$param = substr( $data, $pipePos + 1 );
				$params = explode( '|', $param );
				// After addFileCaption each file should have a caption
				$caption = array_pop( $params );
			} else {
				$target = $data;
			}

			$extensionPos = strrpos( $target, '.' );
			if ( $extensionPos === false ) {
				return $matches[0];
			}
			$extension = substr( $target, $extensionPos + 1 );
			if ( in_array( $extension, $extensions ) ) {
				if ( $caption !== '' ) {
					return "[[Media:{$target}|{$caption}]]";
				}
				return "[[Media:{$target}]]";
			}

			return $matches[0];
		},
		$text );

		return $text;
	}
}
