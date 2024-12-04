<?php

namespace HalloWelt\MigrateDokuwiki\Converter\PostProcessors;

use HalloWelt\MigrateDokuwiki\IProcessor;

class RestoreWrap implements IProcessor {

	/**
	 * @param string $text
	 * @return string
	 */
	public function process( string $text ): string {
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
				'######PRESERVEWRAPWITHDIVSTART######',
				'######PRESERVEWRAPWITHDIVEND######',
				'######PRESERVEWRAPWITHDIVCLOSE######'
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
				'######PRESERVEWRAPWITHSPANSTART######',
				'######PRESERVEWRAPWITHSPANEND######',
				'######PRESERVEWRAPWITHSPANCLOSE######'
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
