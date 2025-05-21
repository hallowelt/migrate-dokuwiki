<?php

namespace HalloWelt\MigrateDokuwiki\Converter\PostProcessors;

use HalloWelt\MigrateDokuwiki\IProcessor;

class RestoreWrap implements IProcessor {

	/**
	 * @param string $text
	 * @param string $path
	 * @return string
	 */
	public function process( string $text, string $path = '' ): string {
		$text = $this->restoreWrapWithDiv( $text );
		$text = $this->restoreWrapWithSpan( $text );
		return $text;
	}

	/**
	 * @param string $text
	 * @return string
	 */
	private function restoreWrapWithDiv( string $text ): string {
		$text = str_replace(
			[
				'#####PRESERVEWRAPOPENDIVSTART#####',
				'#####PRESERVEWRAPOPENDIVEND#####',
				'#####PRESERVEWRAPCLOSEDIV#####'
			],
			[
				'<div',
				'>',
				'</div>'
			],
			$text
		);
		return $text;
	}

	/**
	 * @param string $text
	 * @return string
	 */
	private function restoreWrapWithSpan( string $text ): string {
		$text = str_replace(
			[
				'#####PRESERVEWRAPOPENSPANSTART#####',
				'#####PRESERVEWRAPOPENSPANEND#####',
				'#####PRESERVEWRAPCLOSESPAN#####'
			],
			[
				'<span',
				'>',
				'</span>'
			],
			$text
		);
		return $text;
	}
}
