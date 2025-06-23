<?php

namespace HalloWelt\MigrateDokuwiki\Utility;

class TitleBuilder {

	/** @var array */
	private $titleSegments = [];

	/** @var array */
	private $prefixMap = [];

	/** @var string */
	private $mainpageTitle = 'Main_Page';

	/**
	 * @param array $paths
	 * @param bool $history
	 * @param array $config
	 * @return string
	 */
	public function build( array $paths, $history = false, array $config = [] ) {
		$this->titleSegments = [];
		$this->prefixMap = [];

		if ( isset( $config['space-prefix'] ) && is_array( $config['space-prefix'] ) ) {
			$this->prefixMap = $config['space-prefix'];
		}

		if ( isset( $config['mainpage'] ) && $config['mainpage'] !== '' ) {
			$this->mainpageTitle = $config['mainpage'];
		}

		$title = $this->makeTitleFromPaths( $paths, $history );

		return $title;
	}

	/**
	 * @param string $path
	 * @param bool $history
	 * @return string
	 */
	private function getSubpageText( string $path, $history = false ): string {
		$subpageParts = explode( '.', $path );
		if ( count( $subpageParts ) > 1 ) {
			$fileExtension = array_pop( $subpageParts );
		}
		if ( $history && count( $subpageParts ) > 1 ) {
			$historyTimestamp = array_pop( $subpageParts );
		}
		$subpageText = implode( '.', $subpageParts );
		return $subpageText;
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
			if ( $paths[0] === $this->getSubpageText( $paths[1], $history ) ) {
				$paths[1] = str_replace(
					$this->getSubpageText( $paths[1], $history ),
					$this->mainpageTitle,
					$paths[1]
				);
			}

			if ( isset( $this->prefixMap[$namespace] ) ) {
				$namespace = $this->prefixMap[$namespace];
			} else {
				$namespace = ucfirst( $paths[0] );
				$namespace .= ':';
			}

			unset( $paths[0] );
			$paths = array_values( $paths );
		}

		$subpageText = array_pop( $paths );
		$subpageText = $this->getSubpageText( $subpageText, $history );

		for ( $index = 0; $index < count( $paths ); $index++ ) {
			if ( ( $index === count( $paths ) - 1 )
				&& $this->getSubpageText( $paths[$index], $history ) === $subpageText ) {
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
