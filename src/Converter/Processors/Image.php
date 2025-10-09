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
		$regEx = '#({{)(\s*)(:{0,1})(.*?)(\s*)(}})#';
		$text = preg_replace_callback( $regEx, function ( $matches ) {
			$replacement = $matches[0];
			$matches[0] = '';
			$target = $matches[4];

			$align = '';
			if ( strlen( $matches[2] ) > 0 && strlen( $matches[5] ) ) {
				$align = '#####PRESERVEIMAGEPIPE#####center';
			} elseif ( strlen( $matches[2] ) > 0 ) {
				$align = '#####PRESERVEIMAGEPIPE#####right';
			} elseif ( strlen( $matches[5] ) > 0 ) {
				$align = '#####PRESERVEIMAGEPIPE#####right';
			}

			$src = '';
			$caption = '';
			if ( str_contains( $target, '|' ) ) {
				$markupParts = explode( '|', $target );
				$src = array_shift( $markupParts );
				$caption = array_shift( $markupParts );
			} else {
				$src = $target;
			}

			$queryPos = strpos( $src, '?' );
			$hashPos = strpos( $src, '#' );
			$hash = '';
			$query = '';

			if ( $queryPos && !$hashPos ) {
				$query = substr( $src, $queryPos + 1 );

				$src = substr( $src, 0, $queryPos );
			} elseif ( !$queryPos && $hashPos ) {
				$hash = substr( $src, $hashPos + 1 );

				$src = substr( $src, 0, $hashPos );
			} elseif ( $queryPos < $hashPos ) {
				$hash = substr( $src, $hashPos + 1 );

				$query = substr( $src, $queryPos + 1 );
				$query = str_replace( "#{$hash}", '', $query );

				$src = substr( $src, 0, $queryPos );
			} elseif ( $queryPos > $hashPos ) {
				$query = substr( $src, $queryPos + 1 );

				$hash = substr( $src, $hashPos + 1 );
				$hash = str_replace( "?{$query}", '', $hash );

				$src = substr( $src, 0, $hashPos );
			}

			$linkOnly = false;
			$size = '';
			$queries = explode( '&', $query );
			foreach ( $queries as $item ) {
				if ( str_contains( $item, 'linkonly' ) ) {
					$linkOnly = true;
				} elseif ( $item !== '' ) {
					$matches = [];
					preg_match( '#\d*?x\d*?#', $item, $matches );
					if ( empty( $matches ) ) {
						preg_match( '#\d*?#', $item, $matches );
						if ( empty( $matches ) ) {
							$size = $item;
						}
					} else {
						$size = $item;
					}
				}
			}

			if ( $this->isExternalUrl( $src ) ) {
				$attribs = '';
				if ( $caption !== '' ) {
					$caption = " {$caption}";
				}

				$replacement = "#####PRESERVEIMAGEOPEN#####{$src}{$caption}#####PRESERVEIMAGECLOSE#####";
			} else {
				$fileTitle = $this->findFileTitle( $src );

				$type = "FILE";
				if ( $linkOnly ) {
					$type = "MEDIA";
				}

				$attribs = '';
				if ( $align !== '' ) {
					$attribs = "#####PRESERVEIMAGEPIPE#####{$align}";
				}
				if ( $size !== '' ) {
					$attribs = "#####PRESERVEIMAGEPIPE#####{$size}";
				}
				if ( $caption !== '' ) {
					$attribs = "#####PRESERVEIMAGEPIPE#####{$caption}";
				}

				$replacement = "#####PRESERVEIMAGE{$type}OPEN#####";
				$replacement .= "{$fileTitle}{$attribs}";
				$replacement .= "#####PRESERVEIMAGE{$type}CLOSE#####";
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
					'#####PRESERVEIMAGENAMESPACE#####',
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
		$text = mb_strtolower( $text );

		return $text;
	}
}
