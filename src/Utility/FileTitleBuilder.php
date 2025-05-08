<?php

namespace HalloWelt\MigrateDokuwiki\Utility;

class FileTitleBuilder {

	/** @var array */
	private $titleSegments = [];

	/** @var array */
	private $config = [];

	/** @var bool */
	private $nsFileRepoCompat = false;

	/**
	 * @param array $paths
	 * @param bool $history
	 * @param array $config
	 * @return string
	 */
	public function build( array $paths, bool $history = false, array $config = [] ) {
		$this->config = $config;
		$this->nsFileRepoCompat = $this->getNSFileRepoConfig();
		$this->titleSegments = [];
		if ( isset( $config['put-all-in-this-namespace'] ) ) {
			$title = $this->makeTitleFromPaths( $paths, $history, $config['put-all-in-this-namespace'] );
		} else {
			$title = $this->makeTitleFromPathsWithNamespace( $paths, $history );
		}

		return $title;
	}

	/**
	 * @return bool
	 */
	private function getNSFileRepoConfig(): bool {
		if ( !isset( $this->config['ext-ns-file-repo-compat'] ) ) {
			return false;
		}

		$value = $this->config['ext-ns-file-repo-compat'];
		if ( is_bool( $value ) ) {
			return $value;
		}

		if ( strtolower( $value ) === 'true' ) {
			return true;
		}

		return false;
	}

	/**
	 * @param array $paths
	 * @param bool $history
	 * @param string $namespace
	 * @return string
	 */
	private function makeTitleFromPaths( array $paths, bool $history, string $namespace = '' ): string {
		$filename = array_pop( $paths );
		$filenameParts = explode( '.', $filename );
		$fileExtension = array_pop( $filenameParts );
		if ( $history ) {
			$timestamp = array_pop( $filenameParts );
		}
		$filename = implode( '_', $filenameParts );

		if ( count( $paths ) > 0 ) {
			for ( $index = 0; $index < count( $paths ); $index++ ) {
				if ( ( $index === count( $paths ) - 1 )
					&& $paths[$index] === $filename ) {
					break;
				}
				$this->appendTitleSegment( $paths[$index] );
			}
		}

		$this->appendTitleSegment( $filename );

		$title = implode( '_', $this->titleSegments );
		$title .= ".$fileExtension";

		if ( $namespace !== '' ) {
			$prefix = $namespace . '_';
			if ( $this->nsFileRepoCompat ) {
				$prefix = $namespace . ':';
				$prefix = str_replace( [ '-', ' ' ], '_', $prefix );
				$title = ucfirst( $title );
			}
			$title = $prefix . $title;
		}

		return ucfirst( $title );
	}

	/**
	 * @param array $paths
	 * @param bool $history
	 * @return string
	 */
	private function makeTitleFromPathsWithNamespace( array $paths, bool $history ): string {
		$namespace = '';
		if ( count( $paths ) > 1 ) {
			$namespace = array_shift( $paths );
		}

		$title = $this->makeTitleFromPaths( $paths, $history, $namespace );

		return ucfirst( $title );
	}

	/**
	 *
	 * @param string $segment
	 */
	private function appendTitleSegment( $segment ) {
		$cleanedSegment = $this->cleanTitleSegment( $segment );
		if ( !empty( $cleanedSegment ) ) {
			$this->titleSegments[] = $cleanedSegment;
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
