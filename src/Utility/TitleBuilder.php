<?php

namespace HalloWelt\MigrateDokuwiki\Utility;

class TitleBuilder {

	/** @var array */
	private $titleSegments = [];

	/** @var array */
	private $prefixMap = [];

	/**
	 * @param string $id
	 * @param array $paths
	 * @param bool $history
	 * @param array $config
	 * @return string
	 */
	public function build( string $id, array $paths, $history = false, array $config = [] ) {
		$this->titleSegments = [];
		$this->prefixMap = [];

		if ( isset( $config['space-prefix'] ) && is_array( $config['space-prefix'] ) ) {
			$this->prefixMap = $config['space-prefix'];
		}

		$title = $this->makeTitleFromPaths( $id, $paths, $history );

		return $title;
	}

	/**
	 * @param string $id
	 * @param array $paths
	 * @param bool $history
	 * @return string
	 */
	private function makeTitleFromPaths( string $id, array $paths, $history = false ): string {
		$namespace = '';
		if ( count( $paths ) > 1 ) {
			$namespace = $paths[0];

			$namespaceId = $id;
			if ( str_contains( $id, ':' ) ) {
				$namespaceId = substr( $namespaceId, 0, strpos( $namespaceId, ':' ) );
			}

			// Override namespace by namespace id or meta title
			if ( isset( $this->prefixMap[$namespaceId] ) ) {
				$namespace = $this->prefixMap[$namespaceId];
			} elseif ( isset( $this->prefixMap[$namespace] ) ) {
				$namespace = $this->prefixMap[$namespace];
			} else {
				$namespace = ucfirst( $paths[0] );
				$namespace .= ':';
			}

			unset( $paths[0] );
			$paths = array_values( $paths );
		} elseif ( isset( $this->prefixMap['NS_MAIN'] ) ) {
			$namespace = $this->prefixMap['NS_MAIN'];
		}

		if ( substr_count( $namespace, '/' ) === 1 ) {
			// 'ABC:DEF/'
			$test = trim( $namespace, '/' );
			$test = substr( $test, strpos( $test, ':' ) + 1 );
			$test = str_replace( '-', '_', $test );
			if ( $test === str_replace( '-', '_', $paths[0] ) ) {
				unset( $paths[0] );
				$paths = array_values( $paths );
			}
		} elseif ( substr_count( $namespace, '/' ) > 1 ) {
			// 'ABC:DEF/GEH/'
			$test = trim( $namespace, '/' );
			$test = strtolower( substr( $test, strrpos( $test, '/' ) + 1 ) );
			$test = str_replace( '-', '_', $test );
			if ( $test === $paths[0] ) {
				unset( $paths[0] );
				$paths = array_values( $paths );
			}
		}

		$reverse = array_reverse( explode( ':', $id ) );
		if ( isset( $reverse[2] ) && $reverse[0] === $reverse[1] ) {
			// some dokuwiki have the subpage content inside the directory,
			// ohters inside the parent directory. The first case prduces a double
			// key which creates a double title part.
			array_pop( $paths );
		}

		$subpageText = array_pop( $paths );

		for ( $index = 0; $index < count( $paths ); $index++ ) {
			if ( ( $index === count( $paths ) - 1 )
				&& $paths[$index] === $subpageText ) {
				break;
			}
			$this->appendTitleSegment( $paths[$index] );
		}

		$this->appendTitleSegment( $subpageText );

		$title = implode( '/', $this->titleSegments );

		if ( $namespace !== '' ) {
			$namespace = str_replace( [ '-', ' ' ], '_', $namespace );
			$title = $namespace . $title;
		}

		return trim( $title, '/' );
	}

	/**
	 *
	 * @param string $segment
	 */
	private function appendTitleSegment( $segment ) {
		$cleanedSegment = $this->cleanTitleSegment( $segment );
		if ( !empty( $cleanedSegment ) ) {
			$this->titleSegments[] = ucfirst( $cleanedSegment );
		}
	}

	/**
	 *
	 * @param string $segment
	 * @return string
	 */
	private function cleanTitleSegment( $segment ) {
		$segment = str_replace( ' ', '_', $segment );
		$segment = preg_replace( static::getTitleInvalidRegex(), '_', $segment );
		// Slash is usually a legal char, but not in the segment
		$segment = preg_replace( '/\\//', '_', $segment );
		// MediaWiki normalizes multiple spaces/undescores into one single underscore
		$segment = preg_replace( '#_+#si', '_', $segment );
		$segment = trim( $segment, " _\t" );
		return trim( $segment );
	}

	/**
	 * See
	 * - https://github.com/wikimedia/mediawiki/blob/05ce3b7740951cb26b29bbe3ac9deb610541df48/includes/title/MediaWikiTitleCodec.php#L511-L538
	 * - https://github.com/wikimedia/mediawiki/blob/05ce3b7740951cb26b29bbe3ac9deb610541df48/includes/DefaultSettings.php#L3901-L3925
	 *
	 * @return string
	 */
	public static function getTitleInvalidRegex() {
		static $rxTc = false;
		if ( !$rxTc ) {
			# Matching titles will be held as illegal.
			$rxTc = '/' .
				# Any character not allowed is forbidden...
				"[^ %!\"$&'()*,\\-.\\/0-9:;=?@A-Z\\\\^_`a-z~\\x80-\\xFF+]" .
				# URL percent encoding sequences interfere with the ability
				# to round-trip titles -- you can't link to them consistently.
				'|%[0-9A-Fa-f]{2}' .
				# XML/HTML character references produce similar issues.
				'|&[A-Za-z0-9\x80-\xff]+;' .
				'|&#[0-9]+;' .
				'|&#x[0-9A-Fa-f]+;' .
				'/S';
		}
		return $rxTc;
	}
}
