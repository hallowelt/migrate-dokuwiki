<?php

namespace HalloWelt\MigrateDokuwiki\Converter\Processors;

use HalloWelt\MigrateDokuwiki\IProcessor;
use HalloWelt\MigrateDokuwiki\Utility\CategoryBuilder;

class Image implements IProcessor {

	/** @var array */
	private $mediaNameToTitleMap;

	/** @var array */
	private $advacedConfig;

	/**
	 * @param array $mediaNameToTitleMap
	 * @param array $advacedConfig
	 */
	public function __construct( array $mediaNameToTitleMap, array $advacedConfig = [] ) {
		$this->mediaNameToTitleMap = $mediaNameToTitleMap;
		$this->advacedConfig = $advacedConfig;
	}

	/**
	 * @param string $text
	 * @param string $path
	 * @return string
	 */
	public function process( string $text, string $path = '' ): string {
		$originalText = $text;

		// replace src url
		$regEx = '#({{\s*:{0,1})(.*?)(\s*}})#';
		$text = preg_replace_callback( $regEx, function ( $matches ) {
			$replacement = $matches[0];
			$matches[0] = '';
			$target = $matches[2];

			$src = '';
			$alt = '';
			if ( str_contains( $target, '|' ) ) {
				$markupParts = explode( '|', $target );
				$src = array_shift( $markupParts );
				$alt = array_shift( $markupParts );
			} else {
				$src = $target;
			}
			if ( !$this->isExternalUrl( $src ) ) {
				$queryPos = strpos( $src, '?' );
				$hashPos = strpos( $src, '#' );
				$hash = '';

				$separator = '';
				if ( $queryPos && !$hashPos ) {
					$separator = '?';
				} elseif ( !$queryPos && $hashPos ) {
					$separator = '#';
				} elseif ( $queryPos < $hashPos ) {
					$separator = '?';
				} elseif ( $queryPos > $hashPos ) {
					$separator = '#';
				}

				if ( $separator !== '' ) {
					$hash = substr( $src, strpos( $src, $separator ) );
					$src = str_replace( $hash, '', $src );
				}

				$fileTitle = $this->findFileTitle( $src );
				$matches[2] = $fileTitle . $hash;
				if ( $alt !== '' ) {
					$matches[2] .= "|$alt";
				}
				$replacement = implode( '', $matches );
			} else {
				if ( str_contains( $target, '|' ) ) {
					$matches[2] = str_replace( '|', ' ', $target );
				}
				$replacement = implode( '', $matches );
			}
			return $replacement;
		}, $text );

		if ( !is_string( $text ) ) {
			$category = CategoryBuilder::getPreservedMigrationCategory( 'Image failure' );
			$text = "{$originalText} {$category}";
		}

		return $text;
	}

	/**
	 * @param string $src
	 * @return bool
	 */
	private function isExternalUrl( string $src ): bool {
		$parsedUrl = parse_url( $src );
		if ( !isset( $parsedUrl['scheme'] ) && !isset( $parsedUrl['host'] ) ) {
			return false;
		}
		if ( isset( $parsedUrl['scheme'] ) && in_array( $parsedUrl['scheme'], [ 'http', 'https' ] ) ) {
			return true;
		}
		return false;
	}

	/**
	 * @param string $name
	 * @return string
	 */
	private function findFileTitle( string $name ): string {
		$fileTitle = $name;
		$name = trim( $name );
		$name = strtolower( $name );
		$name = $this->generalizeItem( $name );
		if ( isset( $this->mediaNameToTitleMap[$name] ) ) {
			$fileTitle = $this->mediaNameToTitleMap[$name];
		}

		if ( isset( $this->advacedConfig['ext-ns-file-repo-compat'] )
			&& $this->advacedConfig['ext-ns-file-repo-compat'] === true
		) {
			$namespacePos = strpos( $fileTitle, ':' );
			if ( $namespacePos !== false ) {
				$fileTitle = substr_replace(
					$fileTitle,
					'#####preserveimagenamespace#####',
					$namespacePos, strlen( ':' )
				);
			}
		}
		return $fileTitle;
	}

	/**
	 * @param string $text
	 * @return string
	 */
	private function generalizeItem( string $text ): string {
		$text = str_replace( ' ', '_', $text );
		$text = strtolower( $text );

		return $text;
	}
}
