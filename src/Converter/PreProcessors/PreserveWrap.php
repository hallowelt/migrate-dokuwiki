<?php

namespace HalloWelt\MigrateDokuwiki\Converter\PreProcessors;

use HalloWelt\MigrateDokuwiki\IProcessor;

class PreserveWrap implements IProcessor {

	/**
	 * https://www.dokuwiki.org/plugin:wrap
	 *
	 * @param string $text
	 * @return string
	 */
	public function process( string $text ): string {
		$text = $this->replaceWrapWithDiv( $text );
		$text = $this->replaceWrapWithSpan( $text );

		return $text;
	}

	/**
	 * @param string $text
	 * @return string
	 */
	private function replaceWrapWithDiv( string $text ): string {
		// Opening tag with params
		$regEx = '#<WRAP(.*?)>#';
		$text = preg_replace_callback( $regEx, function ( $matches ) {
			$replacement = $matches[0];

			$id = '';
			$classes = [ 'wrap' ];
			$width = '';
			$lang = [];

			$params = explode( ' ', $matches[1] );
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
				} else if ( is_numeric( strpos( $param, ':' ) ) && strpos( $param, ':' ) === 0) {
					$lang[] = substr( $param, 1 );
				} else if ( is_numeric( strpos( $param, '#' ) ) && strpos( $param, '#' ) === 0) {
					$id = substr( $param, 1 );
				} else {
					$classes[] = $param;
				}
			}

			$data = [];
			if ( $id !== '' ) {
				$data[] = 'id="' . $id . '"';
			}
			if ( !empty( $classes ) ) {
				$data[] = 'class="' . implode( ' ', $classes ) . '"';
			}
			if ( $width !== '' ) {
				$data[] = 'style="width: ' . $width . '"';
			}
			if ( !empty( $lang ) ) {
				$data[] = 'data-lang="' . implode( ' ', $lang ) . '"';
			}

			$replacement = '######PRESERVEWRAPWITHDIVSTART######';
			if ( !empty( $data ) ) {
				$replacement .= ' ';
				$replacement .= implode( ' ', $data );
				$replacement .= ' ';
			}
			$replacement .= '######PRESERVEWRAPWITHDIVEND######';

			return $replacement;
		}, $text );

		// Closing tag
		$text = str_replace(
			'</WRAP>',
			'######PRESERVEWRAPWITHDIVCLOSE######',
			$text
		);

		return $text;
	}

	/**
	 * @param string $text
	 * @return string
	 */
	private function replaceWrapWithSpan( string $text ): string {
		// Opening tag with params
		$regEx = '#<wrap(.*?)>#';
		$text = preg_replace_callback( $regEx, function ( $matches ) {
			$replacement = $matches[0];

			$id = '';
			$classes = [ 'wrap' ];

			$params = explode( ' ', $matches[1] );
			foreach ( $params as $param ) {
				$param = trim( $param );

				if ( $param === '' ) {
					continue;
				}

				if ( is_numeric( strpos( $param, '#' ) ) && strpos( $param, '#' ) === 0) {
					$id = substr( $param, 1 );
				} else {
					$classes[] = $param;
				}
			}

			$data = [];
			if ( $id !== '' ) {
				$data[] = 'id="' . $id . '"';
			}
			if ( !empty( $classes ) ) {
				$data[] = 'class="' . implode( ' ', $classes ) . '"';
			}

			$replacement = '######PRESERVEWRAPWITHSPANSTART######';
			if ( !empty( $data ) ) {
				$replacement .= ' ';
				$replacement .= implode( ' ', $data );
				$replacement .= ' ';
			}
			$replacement .= '######PRESERVEWRAPWITHSPANEND######';

			return $replacement;
		}, $text );

		// Closing tag
		$text = str_replace(
			'</wrap>',
			'######PRESERVEWRAPWITHSPANCLOSE######',
			$text
		);

		return $text;
	}
}