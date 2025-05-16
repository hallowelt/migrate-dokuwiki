<?php

namespace HalloWelt\MigrateDokuwiki\Converter\PostProcessors;

use HalloWelt\MigrateDokuwiki\IProcessor;

class Link implements IProcessor {

	/**
	 * @param string $text
	 * @param string $path
	 * @return string
	 */
	public function process( string $text, string $path = '' ): string {
		$text = $this->replacePageLinkPreserveMarks( $text );
		$text = $this->replaceMailLinkPreserveMarks( $text );
		return $text;
	}

	/**
	 * @param string $text
	 * @return string
	 */
	private function replacePageLinkPreserveMarks( string $text ): string {
		$text = preg_replace(
			[
				'/#####PRESERVEINTERNALLINKOPEN#####/',
				'/#####PRESERVEINTERNALLINKPIPE#####/',
				'/#####PRESERVEINTERNALLINKCLOSE#####/'
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

		/**
		 * @param string $text
		 * @return string
		 */
	private function replaceMailLinkPreserveMarks( string $text ): string {
		$text = preg_replace(
			[
				'/#####PRESERVEMAILLINKOPEN#####/',
				'/#####PRESERVEMAILLINKPIPE#####/',
				'/#####PRESERVEMAILLINKCLOSE#####/'
			],
			[
				'[',
				' ',
				']'
			],
			$text
		);

		return $text;
	}
}
