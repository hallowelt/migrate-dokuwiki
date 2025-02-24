<?php

namespace HalloWelt\MigrateDokuwiki\Converter\PreProcessors;

use HalloWelt\MigrateDokuwiki\IProcessor;

class PreserveCode implements IProcessor {

	/**
	 * @param string $text
	 * @param string $path
	 * @return string
	 */
	public function process( string $text, string $path = '' ): string {
		$text = preg_replace_callback( '#(<code)(.*?)(>)(.*?)(</code>)#s', static function ( $matches ) {
			$matches[1] = '#####PRESERVECODESTART';
			$matches[3] = '#####';

			$text = $matches[4];
			$text = str_replace( "\n", " \\\\ ", $text );
			$matches[4] = $text;

			$matches[5] = '#####PRESERVECODEEND#####';

			unset( $matches[0] );
			return implode( '', $matches );
		}, $text );

		return $text;
	}
}
