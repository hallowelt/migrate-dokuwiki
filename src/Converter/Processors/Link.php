<?php

namespace HalloWelt\MigrateDokuwiki\Converter\Processors;

use HalloWelt\MigrateDokuwiki\IProcessor;

class Link implements IProcessor {

	/** @var array */
	private $pageKeyToTitleMap;

	/**
	 * @param array $pageKeyToTitleMap
	 */
	public function __construct( array $pageKeyToTitleMap ) {
		$this->pageKeyToTitleMap = $pageKeyToTitleMap;
	}

	/**
	 * @param string $text
	 * @param string $path
	 * @return string
	 */
	public function process( string $text, string $path = '' ): string {
		 $regEx = '#(\[\[)(.*?)(\]\])#';
		 $text = preg_replace_callback( $regEx, function ( $matches ) {
			$replacement = $matches[0];
			$target = $matches[2];

			if ( $this->isExternalUrl( $target ) ) {
				return $replacement;
			}

			if ( strpos( $matches[2], '|' ) ) {
				// replace link target for link with label
				$linkParts = explode( '|', $matches[2] );
				for ( $index = 0; $index < count( $linkParts ); $index++ ) {
					// trim whitespaces form the link parts to avoid issues with pageKeyToTitleMap
					$value = $linkParts[$index];
					$linkParts[$index] = trim( $value );
				}
				$target = $linkParts[0];
				$target = trim( $target, ':' );

				$hash = '';
				$hashPos = strpos( $target, '#' );
				if ( $hashPos ) {
					$hash = substr( $target, strpos( $target, '#' ) );
					$target = str_replace( $hash, '', $target );
				}

				$targetKey = $this->generalizeItem( $target );
				var_dump( $targetKey );
				if ( isset( $this->pageKeyToTitleMap[$targetKey] ) ) {
					$linkParts[0] = $this->pageKeyToTitleMap[$targetKey] . $hash;
					$lastLinkPart = array_key_last( $linkParts );
					if ( $linkParts[$lastLinkPart] === '' ) {
						// If the last part is empty conversion will last in |]]. Therefore we set the target as label
						$linkParts[$lastLinkPart] = $this->pageKeyToTitleMap[$targetKey];
					}
					$replacement = implode( '###PRESERVEINTERNALLINKPIPE###', $linkParts );
					$replacement = $this->wrapPreserveMarker( $replacement );
				}
				return $replacement;
			}

			// replace link target for link without label
			$hash = '';
			$hashPos = strpos( $target, '#' );
			if ( $hashPos ) {
				$hash = substr( $target, strpos( $target, '#' ) );
				$target = str_replace( $hash, '', $target );
			}

			$target = trim( $target, ':' );
			$targetKey = $this->generalizeItem( $target );
			if ( isset( $this->pageKeyToTitleMap[$targetKey] ) ) {
				$replacement = $this->pageKeyToTitleMap[$targetKey] . $hash;
				$replacement = $this->wrapPreserveMarker( $replacement );
			}
			return $replacement;
		 }, $text );

		return $text;
	}

	/**
	 * @param string $target
	 * @return bool
	 */
	private function isExternalUrl( string $target ): bool {
		$parsedUrl = parse_url( $target );
		if ( !isset( $parsedUrl['scheme'] ) && !isset( $parsedUrl['host'] ) ) {
			return false;
		}
		if ( isset( $parsedUrl['scheme'] ) && in_array( $parsedUrl['scheme'], [ 'http', 'https' ] ) ) {
			return true;
		}
		return false;
	}

	/**
	 * @param string $text
	 * @return string
	 */
	private function wrapPreserveMarker( string $text ): string {
		return "###PRESERVEINTERNALLINKOPEN###$text###PRESERVEINTERNALLINKCLOSE###";
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
