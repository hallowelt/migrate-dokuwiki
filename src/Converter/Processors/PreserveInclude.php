<?php

namespace HalloWelt\MigrateDokuwiki\Converter\Processors;

use HalloWelt\MigrateDokuwiki\IProcessor;
use HalloWelt\MigrateDokuwiki\Utility\AccentedChars;
use HalloWelt\MigrateDokuwiki\Utility\CategoryBuilder;

class PreserveInclude implements IProcessor {

	/** @var array */
	private $pageKeyToTitleMap;

	/**
	 * @param array $pageKeyToTitleMap
	 */
	public function __construct( array $pageKeyToTitleMap ) {
		$this->pageKeyToTitleMap = $pageKeyToTitleMap;
	}

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

	/**
	 * @param string $target
	 * @return string
	 */
	private function getTargetWikiTitle( string $target ): string {
		$targetKey = $this->generalizeItem( $target );
		if ( isset( $this->pageKeyToTitleMap[$targetKey] ) ) {
			return $this->pageKeyToTitleMap[$targetKey];
		}
		// Try again with accented characters
		$targetKey = AccentedChars::normalizeAccentedText( $targetKey );
		if ( isset( $this->pageKeyToTitleMap[$targetKey] ) ) {
			return $this->pageKeyToTitleMap[$targetKey];
		}
		// Guess wiki title if targetKey is not set in map
		$guessedTitle = $this->getGuessedTitle( $target );
		return $guessedTitle;
	}

	/**
	 * @param string $text
	 * @return string
	 */
	private function generalizeItem( string $text ): string {
		$text = str_replace( ' ', '_', $text );
		$text = mb_strtolower( $text );

		return $text;
	}

	/**
	 * Remove last part of the key and try to find a match in map
	 * to build a wiki page title
	 *
	 * @param string $text
	 * @return string
	 */
	private function getGuessedTitle( string $text ): string {
		$trimmed = trim( $text, ':' );
		$parts = explode( ':', $trimmed );
		$title = '';
		for ( $index = 0; $index < count( $parts ); $index++ ) {
			$guessed = array_slice( $parts, 0, $index + 1 );
			$guessedKey = implode( ':', $guessed );
			$guessedKey = $this->generalizeItem( $guessedKey );
			if ( isset( $this->pageKeyToTitleMap[$guessedKey] ) ) {
				$title = $this->pageKeyToTitleMap[$guessedKey];
			} else {
				$tail = $parts[$index];
				$tail = ucfirst( $tail );
				if ( $title === $tail ) {
					// Dokuwiki pages with subpages can have double name in key
					// abc:abc:def
					continue;
				}
				$title = "{$title}/{$tail}";
				$title = trim( $title, '/' );
			}
		}
		return $title;
	}
}
