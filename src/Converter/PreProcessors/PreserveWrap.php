<?php

namespace HalloWelt\MigrateDokuwiki\Converter\PreProcessors;

use HalloWelt\MigrateDokuwiki\IProcessor;
use HalloWelt\MigrateDokuwiki\Utility\CategoryBuilder;

class PreserveWrap implements IProcessor {

	/**
	 * https://www.dokuwiki.org/plugin:wrap
	 *
	 * @param string $text
	 * @param string $path
	 * @return string
	 */
	public function process( string $text, string $path = '' ): string {
		$text = $this->replaceWrapWithDiv( $text );
		$text = $this->replaceWrapWithSpan( $text );

		return $text;
	}

	/**
	 * @param string $text
	 * @return string
	 */
	private function replaceWrapWithDiv( string $text ): string {
		$originalText = $text;

		$regEx = '#(.*?)(<){1}(WRAP|block|div){1}(.*?)(>){1}(.*?)(<\/){1}(\3){1}(>){1}#s';
		$text = preg_replace_callback( $regEx, static function ( $matches ) {
			$replacement = $matches[0];
			$type = $matches[3];
			$params = trim( $matches[4] );

			if ( $type === 'div' ) {
				if ( strlen( $params ) === 0 || strpos( $params, '"' ) !== false ) {
					// Real htm div and not a WRAP element
					return $replacement;
				}
			}

			// break lines befor div
			$matches[2] = "\n";
			$matches[3] = '#####PRESERVEWRAPOPENDIVSTART#####';
			$matches[5] = '#####PRESERVEWRAPOPENDIVEND#####';
			// break lines after div
			$matches[7] = '#####PRESERVEWRAPCLOSEDIV#####';
			$matches[8] = '';
			$matches[9] = "\n";

			$attribs = self::processAttributes( $params );
			if ( !empty( $attribs ) ) {
				$matches[4] = ' ' . implode( ' ', $attribs ) . ' ';
			} else {
				$matches[4] = '';
			}

			unset( $matches[0] );
			$matches = array_values( $matches );

			$replacement = implode( '', $matches );
			return $replacement;
		}, $text );

		if ( !is_string( $text ) ) {
			$category = CategoryBuilder::getPreservedMigrationCategory( 'WRAP failure' );
			$text = "{$originalText} {$category}";
		}

		return $text;
	}

	/**
	 * @param string $text
	 * @return string
	 */
	private function replaceWrapWithSpan( string $text ): string {
		$originalText = $text;

		$regEx = '#(.*?)(<)(wrap|inline|span)(.*?)(>)(.*?)(<\/)(\3)(>)#s';
		$text = preg_replace_callback( $regEx, static function ( $matches ) {
			$replacement = $matches[0];

			$type = $matches[3];
			$params = trim( $matches[4] );

			if ( $type === 'span' ) {
				if ( strlen( $params ) === 0 || strpos( $params, '"' ) !== false ) {
					// Real htm div and not a WRAP element
					return $replacement;
				}
			}

			$matches[2] = '';
			$matches[3] = '#####PRESERVEWRAPOPENSPANSTART#####';
			$matches[5] = '#####PRESERVEWRAPOPENSPANEND#####';
			$matches[7] = '#####PRESERVEWRAPCLOSESPAN#####';
			$matches[8] = $matches[9] = '';

			$attribs = self::processAttributes( $params );
			if ( !empty( $attribs ) ) {
				$matches[4] = ' ' . implode( ' ', $attribs ) . ' ';
			} else {
				$matches[4] = '';
			}

			unset( $matches[0] );
			$matches = array_values( $matches );

			$replacement = implode( '', $matches );
			return $replacement;
		}, $text );

		if ( !is_string( $text ) ) {
			$category = CategoryBuilder::getPreservedMigrationCategory( 'wrap failure' );
			$text = "{$originalText} {$category}";
		}

		return $text;
	}

	/**
	 * @param string $data
	 * @return array
	 */
	private static function processAttributes( string $data ): array {
		$id = '';
		$width = '';
		$classes = [ 'wrap' ];
		$lang = [];
		$attribs = [];

		$params = explode( ' ', $data );
		foreach ( $params as $param ) {
			$param = trim( $param );

			if ( $param === '' ) {
				continue;
			}

			$widthMatches = [];
			preg_match(
				'#(\d+)([%,px,em,rem,ex,ch,vw,vh,pt,pc,cm,mm,in])#',
				$param,
				$widthMatches
			);
			if ( !empty( $widthMatches ) ) {
				// is a width
				$width = $param;
				if ( strrpos( $width, ';', -1 ) === false ) {
					$width = "{$width};";
				}
			} elseif ( is_numeric( strpos( $param, ':' ) ) && strpos( $param, ':' ) === 0 ) {
				$lang[] = substr( $param, 1 );
			} elseif ( is_numeric( strpos( $param, '#' ) ) && strpos( $param, '#' ) === 0 ) {
				$id = substr( $param, 1 );
			} else {
				$classes[] = $param;
			}
		}

		$attribs = [];
		if ( $id !== '' ) {
			$attribs[] = 'id="' . $id . '"';
		}
		if ( !empty( $classes ) ) {
			$attribs[] = 'class="' . implode( ' ', $classes ) . '"';
		}
		if ( $width !== '' ) {
			$attribs[] = 'style="width: ' . $width . '"';
		}
		if ( !empty( $lang ) ) {
			$attribs[] = 'data-lang="' . implode( ' ', $lang ) . '"';
		}

		return $attribs;
	}
}
