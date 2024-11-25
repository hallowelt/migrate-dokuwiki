<?php

namespace HalloWelt\MigrateDokuwiki\Converter\PostProcessors;

use HalloWelt\MigrateDokuwiki\IProcessor;

class Color implements IProcessor {

	/**
	 * @param string $text
	 * @return string
	 */
	public function process( string $text ): string {
		// remove leading / which is placed by pandoc between File: and the file title
		$regEx = '#<color(.*?)>(.*?)</color>#';
		$text = preg_replace_callback( $regEx, static function ( $matches ) {
			$color =  $matches[1];
            $text =  $matches[2];
			$replacement = '<span style="color: ' . trim( $color ) . '">' . $text . '</span>';
			return $replacement;
		}, $text );
		return $text;
	}
}
