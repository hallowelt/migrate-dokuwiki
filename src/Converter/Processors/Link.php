<?php

namespace HalloWelt\MigrateDokuwiki\Converter\Processors;

use HalloWelt\MigrateDokuwiki\IProcessor;
use HalloWelt\MigrateDokuwiki\Utility\AccentedChars;
use HalloWelt\MigrateDokuwiki\Utility\CategoryBuilder;

class Link implements IProcessor {

	/** @var array */
	private $pageKeyToTitleMap;

	/**
	 * @param array $pageKeyToTitleMap
	 */
	public function __construct( array $pageKeyToTitleMap ) {
		$this->pageKeyToTitleMap = $pageKeyToTitleMap;
	}

	/**
	 * @param string $text
	 * @param string $path
	 * @return string
	 */
	public function process( string $text, string $path = '' ): string {
		$originalText = $text;

		 $regEx = '#(\[\[)(.*?)(\]\])#s';
		 $text = preg_replace_callback( $regEx, function ( $matches ) {
			$replacement = $matches[0];
			$target = trim( $matches[2] );

			if ( $this->isExternalUrl( $target ) ) {
				return $replacement;
			}

			if ( $this->isMailToLink( $target ) ) {
				$replacement = $this->handleMailToLink( $target );
				return $replacement;
			}

			$replacement = $this->handlePageLink( $target );
			return $replacement;
		 }, $text );

		if ( !is_string( $text ) ) {
			$category = CategoryBuilder::getPreservedMigrationCategory( 'Link failure' );
			$text = "{$originalText} {$category}";
		}

		return $text;
	}

	/**
	 * @param string $target
	 * @return bool
	 */
	private function isExternalUrl( string $target ): bool {
		$parsedUrl = parse_url( $target );
		if ( !isset( $parsedUrl['scheme'] ) && !isset( $parsedUrl['host'] ) ) {
			return false;
		}
		if ( isset( $parsedUrl['scheme'] ) && in_array( $parsedUrl['scheme'], [ 'http', 'https' ] ) ) {
			return true;
		}
		return false;
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

	/**
	 * @param string $data
	 * @return bool
	 */
	private function hasLabel( string $data ): bool {
		if ( strpos( $data, '|' ) === false ) {
			return false;
		}
		return true;
	}

	/**
	 * @param string $data
	 * @return bool
	 */
	private function isMailToLink( string $data ): bool {
		if ( $this->hasLabel( $data ) ) {
			$pipePos = strpos( $data, '|' );
			$data = substr( $data, 0, $pipePos );
			$data = trim( $data );
		}

		if ( strpos( $data, '@' ) !== false ) {
			return true;
		}
		return false;
	}

	/**
	 * @param string $data
	 * @return string
	 */
	private function handleMailToLink( string $data ): string {
		$replacement = $data;

		if ( $this->hasLabel( $data ) ) {
			$linkParts = $this->getLinkParts( $data );
			$linkParts[0] = trim( $linkParts[0], ':' );
			if ( $linkParts[1] === '' ) {
				// If label is empty [[e@mail | ]]
				$linkParts[1] = $linkParts[0];
			}
			$replacement = $this->getPreservedMailLinkReplacement( $linkParts );
		} else {
			$data = trim( $data, ':' );
			// Build linkParts [ target, label ];
			$linkParts = [ $data, $data ];
			$replacement = $this->getPreservedMailLinkReplacement( $linkParts );
		}

		return $replacement;
	}

	/**
	 * @param string $data
	 * @return string
	 */
	private function handlePageLink( string $data ): string {
		$replacement = $data;

		if ( $this->hasLabel( $data ) ) {
			$linkParts = $this->getLinkParts( $data );
			$target = trim( $linkParts[0], ':' );
			$hash = $this->getHash( $target );

			$title = $this->getTargetWikiTitle( $target );
			$linkParts[0] = $title . $hash;
			$linkParts = $this->fixEmptyLabel( $linkParts, $title );

			$replacement = $this->getPreservedLinkReplacement( $linkParts );

			$targetKey = $this->generalizeItem( $target );
			if ( $title !== '' && !isset( $this->pageKeyToTitleMap[$targetKey] ) ) {
				// Try again with accented characters
				$targetKey = AccentedChars::normalizeAccentedText( $targetKey );
				if ( $title !== '' && !isset( $this->pageKeyToTitleMap[$targetKey] ) ) {
					$category = CategoryBuilder::getPreservedMigrationCategory( 'Guessed link target' );
					$replacement .= " {$category}";
				}
			}
		} else {
			$hash = $this->getHash( $data );
			$target = $this->getTargetWikiTitle( $data );

			$replacement = $this->getPreservedLinkReplacement( [ $target . $hash ] );

			$targetKey = $this->generalizeItem( $data );
			if ( $target !== '' && !isset( $this->pageKeyToTitleMap[$targetKey] ) ) {
				$category = CategoryBuilder::getPreservedMigrationCategory( 'Guessed link target' );
				$replacement .= " {$category}";
			}
		}

		return $replacement;
	}

	/**
	 * @param string &$data
	 * @return string
	 */
	private function getHash( string &$data ): string {
		$hash = '';
		$hashPos = strpos( $data, '#' );
		if ( $hashPos ) {
			$hash = substr( $data, strpos( $data, '#' ) );
			$data = str_replace( $hash, '', $data );
		}

		return $hash;
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
	 * @param array $linkParts
	 * @param string $title
	 * @return array
	 */
	private function fixEmptyLabel( array $linkParts, string $title ): array {
		$lastKey = array_key_last( $linkParts );
		if ( $linkParts[$lastKey] === '' ) {
			// If the last part is empty conversion will last in |]]. Therefore we set the target as label
			$linkParts[$lastKey] = $title;
		}

		return $linkParts;
	}

	/**
	 * @param string $data
	 * @return array
	 */
	private function getLinkParts( string $data ): array {
		$linkParts = explode( '|', $data );
		for ( $index = 0; $index < count( $linkParts ); $index++ ) {
			// trim whitespaces form the link parts to avoid issues with pageKeyToTitleMap
			$value = $linkParts[$index];
			$linkParts[$index] = trim( $value );
		}
		return $linkParts;
	}

	/**
	 * @param array $linkParts
	 * @return string
	 */
	private function getPreservedLinkReplacement( array $linkParts ): string {
		$data = implode( '#####PRESERVEINTERNALLINKPIPE#####', $linkParts );
		$replacement = "#####PRESERVEINTERNALLINKOPEN#####$data#####PRESERVEINTERNALLINKCLOSE#####";
		if ( $linkParts[0] === '' ) {
			$replacement .= ' ' . CategoryBuilder::getPreservedMigrationCategory( 'Missing link target' );
		}
		return $replacement;
	}

	/**
	 * @param array $linkParts
	 * @return string
	 */
	private function getPreservedMailLinkReplacement( array $linkParts ): string {
		$data = implode( '#####PRESERVEMAILLINKPIPE#####', $linkParts );
		$replacement = "#####PRESERVEMAILLINKOPEN#####$data#####PRESERVEMAILLINKCLOSE#####";
		if ( $linkParts[0] === '' ) {
			$replacement .= ' ' . CategoryBuilder::getPreservedMigrationCategory( 'Missing link target' );
		}
		return $replacement;
	}
}
