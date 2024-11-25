<?php

namespace HalloWelt\MigrateDokuwiki\Converter\PostProcessors;

use HalloWelt\MigrateDokuwiki\IProcessor;

class Hidden implements IProcessor {

	/**
	 * @param string $text
	 * @return string
	 */
	public function process( string $text ): string {
		// remove leading / which is placed by pandoc between File: and the file title
		$regEx = '#<hidden onHidden="(.*?)" onVisible="(.*?)" (.*?)>(.*?)</hidden>#';
		$text = preg_replace_callback( $regEx, static function ( $matches ) {
			$show = $matches[1];
			$hide = $matches[2];
			$flags = $matches[3];
			$text = $matches[4];
			
			
			$replacement = '<div class="mw-collapsible" data-expandtext="' . $show . '" data-collapsetext="' . $hide . '">';
			$replacement .= $text;
			$replacement .= '</div>';

			return $replacement;
		}, $text );
		return $text;
	}
}
