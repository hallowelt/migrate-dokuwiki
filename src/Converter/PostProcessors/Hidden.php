<?php

namespace HalloWelt\MigrateDokuwiki\Converter\PostProcessors;

use HalloWelt\MigrateDokuwiki\IProcessor;

class Hidden implements IProcessor {

	/**
	 * @param string $text
	 * @return string
	 */
	public function process( string $text ): string {
		$originalText = $text;

		// replace hidden elements with mw-collapsible
		$regEx = '#(<|&lt;)hidden(.*?)(>|&gt;)(.*?)(<|&lt;)\/hidden(>|&gt;)#si';
		$text = preg_replace_callback( $regEx, static function ( $matches ) {
			$params = $matches[2];

			$show = '';
			if ( strpos( $params, 'onHidden=&quot;' ) ) {
				$start = strpos( $params, 'onHidden=&quot;' );
				$stop = strpos( $params, '&quot;', $start + 16 );
				$showText = substr( $params, $start + 15, $stop - $start - 15 );
				$show = ' data-expandtext="' . $showText . '"';
			} elseif ( strpos( $params, 'onHidden="' ) ) {
				$start = strpos( $params, 'onHidden="' );
				$stop = strpos( $params, '"', $start + 11 );
				$showText = substr( $params, $start + 10, $stop - $start - 10 );
				$show = ' data-expandtext="' . $showText . '"';
			}

			$hide = '';
			if ( strpos( $params, 'onVisible=&quot;' ) ) {
				$start = strpos( $params, 'onVisible=&quot;' );
				$stop = strpos( $params, '&quot;', $start + 17 );
				$hideText = substr( $params, $start + 16, $stop - $start - 16 );
				$hide = ' data-collapsetext="' . $hideText . '"';
			} elseif ( strpos( $params, 'onVisible="' ) ) {
				$start = strpos( $params, 'onVisible="' );
				$stop = strpos( $params, '"', $start + 12 );
				$hideText = substr( $params, $start + 11, $stop - $start - 11 );
				$hide = ' data-collapsetext="' . $hideText . '"';
			}

			$text = $matches[4];

			$replacement = '<div class="mw-collapsible"' . $show . $hide . '>';
			$replacement .= $text;
			$replacement .= '</div>';

			return $replacement;
		}, $text );

		if ( $text === null ) {
			$text = $originalText;
		}

		return $text;
	}
}
