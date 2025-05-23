<?php

namespace HalloWelt\MigrateDokuwiki\Utility;

class TitleBuilder {

	/** @var array */
	private $titleSegments = [];

	/** @var array */
	private $prefixMap = [];

	/** @var bool */
	private $keepPrefix = false;

	/**
	 * @param array $paths
	 * @param bool $history
	 * @param array $config
	 * @return string
	 */
	public function build( array $paths, $history = false, array $config = [] ) {
		$this->titleSegments = [];
		$this->prefixMap = [];
		$this->keepPrefix = [];

		if ( isset( $config['space-prefix'] ) && is_array( $config['space-prefix'] ) ) {
			$this->prefixMap = $config['space-prefix'];
		}

		if ( isset( $config['keep-mapped-prefix'] ) && is_bool( $config['keep-mapped-prefix'] ) ) {
			$this->keepPrefix = $config['keep-mapped-prefix'];
		}

		$title = $this->makeTitleFromPaths( $paths, $history );

		return $title;
	}

	/**
	 * @param array $paths
	 * @param bool $history
	 * @return string
	 */
	private function makeTitleFromPaths( array $paths, $history = false ): string {
		$namespace = '';
		if ( count( $paths ) > 1 ) {
			$namespace = $paths[0];
			if ( isset( $this->prefixMap[$namespace] ) ) {
				$namespace = $this->prefixMap[$namespace];

				if ( !$this->keepPrefix ) {
					$dropNamespace = array_shift( $paths );
				}
			} else {
				$dropNamespace = array_shift( $paths );
			}
		}

		$subpageName = array_pop( $paths );
		$subpageParts = explode( '.', $subpageName );
		$fileExtension = array_pop( $subpageParts );
		if ( $history ) {
			$historyTimestamp = array_pop( $subpageParts );
		}
		$subpageName = implode( '.', $subpageParts );

		for ( $index = 0; $index < count( $paths ); $index++ ) {
			if ( ( $index === count( $paths ) - 1 )
				&& $paths[$index] === $subpageName ) {
				break;
			}
			$this->appendTitleSegment( $paths[$index] );
		}

		$this->appendTitleSegment( $subpageName );

		$title = implode( '/', $this->titleSegments );

		if ( $namespace !== '' ) {
			$namespace = str_replace( [ '-', ' ' ], '_', $namespace );
			$prefix = ucfirst( $namespace ) . ':';
			$title = $prefix . $title;
		}

		return $title;
	}

	/**
	 * @param array $paths
	 * @param bool $history
	 * @return string
	 */
	private function makeTitleFromPathsWithNamespace( array $paths, $history = false ): string {
		$namespace = '';
		if ( count( $paths ) > 1 ) {
			$namespace = array_shift( $paths );
		}

		$title = $this->makeTitleFromPaths( $paths, $history, $namespace );

		return $title;
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
