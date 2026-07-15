<?php

namespace HalloWelt\MigrateDokuwiki\Converter\Processors;

use HalloWelt\MigrateDokuwiki\Utility\CategoryBuilder;

class PreserveInclude extends Link {

	/**
	 * https://www.dokuwiki.org/plugin:include
	 *
	 * @param string $text
	 * @param string $path
	 * @return string
	 */
	public function process( string $text, string $path = '' ): string {
		$originalText = $text;

		 $regEx = '#(\{\{)(page|section|namespace|tag|tagtopic)>(.*?)(\}\})#s';
		 $text = preg_replace_callback( $regEx, function ( $matches ) {
			$type = $matches[2];
			$query = $matches[3];

			$target = $query;
			$flags = [];
			if ( strpos( $query, '&' ) !== false ) {
				$queryParts = explode( '&', $query );
				$target = $queryParts[0];
				unset( $queryParts[0] );
				$flags = array_values( $queryParts );
			}

			$section = '';
			$hashPos = strpos( $target, '#' );
			if ( $hashPos !== false ) {
				$section = substr( $target, $hashPos + 1 );
				$target = substr( $target, 0, $hashPos );
			}

			$title = $this->getTargetWikiTitle( $target );

			$pipe = '#####PRESERVEINCLUDEPIPE#####';

			$template = "#####PRESERVEINCLUDEOPEN#####";

			if ( $type !== '' ) {
				$template .= "{$pipe} type = {$type}";
			}
			if ( $title !== '' ) {
				$template .= " {$pipe} page = {$title}";
			}
			if ( $section !== '' ) {
				$template .= " {$pipe} section = {$section}";
			}
			if ( !empty( $flags ) ) {
				$flagsString = implode( ', ', $flags );
				$template .= " {$pipe} flags = {$flagsString}";
			}

			$template .= "#####PRESERVEINCLUDECLOSE#####";

			return $template;
		 }, $text );

		if ( !is_string( $text ) ) {
			$category = CategoryBuilder::getPreservedMigrationCategory( 'Include failure' );
			$text = "{$originalText} {$category}";
		}

		return $text;
	}
}
