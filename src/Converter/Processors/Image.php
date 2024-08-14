<?php

namespace HalloWelt\MigrateDokuwiki\Converter\Processors;

use HalloWelt\MigrateDokuwiki\IProcessor;

class Image implements IProcessor {

	/** @var array */
	private $mediaNameToTitleMap;

	/**
	 * @param array $mediaNameToTitleMap
	 */
	public function __construct( array $mediaNameToTitleMap ) {
		$this->mediaNameToTitleMap = $mediaNameToTitleMap;
	}

	/**
	 * @param string $text
	 * @return string
	 */
	public function process( string $text ): string {
		// replace src url
		$regEx = '#({{\s*:{0,1})(.*?)(\s*}})#';
		$text = preg_replace_callback( $regEx, function ( $matches ) {
			$replacement = $matches[0];
			$matches[0] = '';
			$target = $matches[2];

			$src = '';
			if ( str_contains( $target, '|' ) ) {
				$markupParts = explode( '|', $target );
				$src = array_shift( $markupParts );
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
				$replacement = implode( '', $matches );
			}
			return $replacement;
		}, $text );
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
		if ( isset( $this->mediaNameToTitleMap[$name] ) ) {
			$fileTitle = $this->mediaNameToTitleMap[$name][0];
		}
		return $fileTitle;
	}
}
