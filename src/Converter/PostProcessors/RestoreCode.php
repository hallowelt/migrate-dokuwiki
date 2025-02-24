<?php

namespace HalloWelt\MigrateDokuwiki\Converter\PostProcessors;

use HalloWelt\MigrateDokuwiki\IProcessor;

class RestoreCode implements IProcessor {

	/**
	 * @param string $text
	 * @param string $path
	 * @return string
	 */
	public function process( string $text, string $path = '' ): string {
		$text = preg_replace_callback(
			'/(#####PRESERVECODESTART)(.*?)(#####)(.*?)(#####PRESERVECODEEND#####)/s', static function ( $matches ) {
				$lang = '';
				if ( trim( $matches[2] ) !== '' ) {
					$lang = ' lang=' . trim( $matches[2] );
				}

				if ( strpos( $matches[4], '<br />' ) !== false ) {
					$matches[1] = '<syntaxhighlight' . $lang;
					$matches[3] = '>';

					$text = $matches[4];
					$text = str_replace( "<br />", "\n", $text );
					$matches[4] = $text;

					$matches[5] = '</syntaxhighlight>';
				} else {
					$matches[1] = '<code';
					unset( $matches[2] );
					$matches[3] = '>';
					$matches[5] = '</code>';
				}

				unset( $matches[0] );
				return implode( '', $matches );
			}, $text );

		return $text;
	}

}
