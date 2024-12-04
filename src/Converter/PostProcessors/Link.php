<?php

namespace HalloWelt\MigrateDokuwiki\Converter\PostProcessors;

use HalloWelt\MigrateDokuwiki\IProcessor;

class Link implements IProcessor {

	/**
	 * @param string $text
	 * @return string
	 */
	public function process( string $text ): string {
		$text = preg_replace(
			[
				'/###PRESERVEINTERNALLINKOPEN###/',
				'/###PRESERVEINTERNALLINKPIPE###/',
				'/###PRESERVEINTERNALLINKCLOSE###/'
			],
			[
				'[[',
				'|',
				']]'
			],
			$text
		);

		return $text;
	}
}
