<?php

namespace HalloWelt\MigrateDokuwiki\Converter\PostProcessors;

use HalloWelt\MigrateDokuwiki\IProcessor;

class Link implements IProcessor {

	/**
	 * @param string $text
	 * @return string
	 */
	public function process( string $text ): string {
		// remove leading / which is placed by pandoc between File: and the file title
		$text = preg_replace(
			[
				'/###PRESERVEINTERNALLINKOPEN###/',
				'/###PRESERVEINTERNALLINKCLOSE###/'
			],
			[
				'[[',
				']]'
			],
			$text
		);
	
		return $text;
	}
}
