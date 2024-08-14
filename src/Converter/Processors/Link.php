<?php

namespace HalloWelt\MigrateDokuwiki\Converter\Processors;

use HalloWelt\MigrateDokuwiki\IProcessor;

class Link implements IProcessor {

    /** @var array */
    private $pageKeyToTitleMap;

    /**
     * 
     */
    public function __construct( array $pageKeyToTitleMap ) {
        $this->pageKeyToTitleMap = $pageKeyToTitleMap;
    }

    /**
     * @param string $text
     * @return string
     */
    public function process( string $text ): string {
        // replace link target for link with label
        $regEx = '#(\[\[)(.*?)(\|.*?\]\])#';
        $text = preg_replace_callback( $regEx, function( $matches ) {
			$replacement = $matches[0];
			$target = $matches[2];

			if ( $this->isExternalUrl( $target ) ) {
				return $replacement;
			}

			$hash = '';
			$hashPos = strpos( $target, '#' );
			if ( $hashPos ) {
				$hash = substr( $target, strpos( $target, '#' ) );
				$target = str_replace( $hash, '', $target );
			}

			$target= trim( $target, ':' );
			if ( isset( $this->pageKeyToTitleMap[$target] ) ) {
				$matches[2] = $this->pageKeyToTitleMap[$target][0] . $hash;
				unset( $matches[0] );
				$replacement = implode( '', $matches );
			}
            return $replacement;
        }, $text );

         // replace link target for link without label
         $regEx = '#(\[\[)(.*?)(\]\])#';
         $text = preg_replace_callback( $regEx, function( $matches ) {
			$replacement = $matches[0];
			$target = $matches[2];

			if ( $this->isExternalUrl( $target ) ) {
				return $replacement;
			}

			$hash = '';
            $hashPos = strpos( $target, '#' );
			if ( $hashPos ) {
				$hash = substr( $target, strpos( $target, '#' ) );
				$target = str_replace( $hash, '', $target );
			}
			
			$target= trim( $target, ':' );
			if ( isset( $this->pageKeyToTitleMap[$target] ) ) {
				$matches[2] = $this->pageKeyToTitleMap[$target][0] . $hash;
				unset( $matches[0] );
				$replacement = implode( '', $matches );
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
}