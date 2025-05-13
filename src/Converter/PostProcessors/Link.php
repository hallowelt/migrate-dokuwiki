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
		$text = $this->addLabel( $text );
		$text = $this->replacePreserveMarks( $text );
		return $text;
	}

	/**
	 * @param string $text
	 * @return string
	 */
	private function addLabel( string $text ): string {
		$regEx = '/###PRESERVEINTERNALLINKOPEN###(.*?)###PRESERVEINTERNALLINKCLOSE###/';
		$pipeMarker = '###PRESERVEINTERNALLINKPIPE###';
		$text = preg_replace_callback( $regEx, static function ( $matches ) use ( $pipeMarker ) {
			$text = $matches[1];
			if ( strpos( $text, $pipeMarker ) === false ) {
				return $matches[0];
			}

			if ( strrpos( $text, $pipeMarker ) === strlen( $text ) - strlen( $pipeMarker ) ) {
				$target = substr( $text, 0, strpos( $text, $pipeMarker ) );
				$replacement = '###PRESERVEINTERNALLINKOPEN###';
				$replacement .= $text . $target;
				$replacement .= '###PRESERVEINTERNALLINKCLOSE###';
				return $replacement;
			}

			return $matches[0];
		}, $text );
		return $text;
	}

	/**
	 * @param string $text
	 * @return string
	 */
	private function replacePreserveMarks( string $text ): string {
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
