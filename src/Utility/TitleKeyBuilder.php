<?php

namespace HalloWelt\MigrateDokuwiki\Utility;

class TitleKeyBuilder {

	/** @var array */
	private $titleSegments = [];

	/**
	 * @param array $paths
	 * @return string
	 */
	public function build( array $paths ) {
		$this->titleSegments = [];
		$key = $this->makeTitleKeyFromPaths( $paths );
		return strtolower( $key );
	}

	/**
	 * @param array $paths
	 * @return string
	 */
	public function buildDoubleKey( array $paths ) {
		$this->titleSegments = [];
		$defaultKey = $this->makeTitleKeyFromPaths( $paths );
		$doubleKey = end( $this->titleSegments );
		$this->titleSegments[] = $doubleKey;
		$key = implode( ':', $this->titleSegments );

		return strtolower( $key );
	}

	/**
	 * @param array $paths
	 * @return string
	 */
	private function makeTitleKeyFromPaths( array $paths ): string {
		$subpageName = array_pop( $paths );
		$subpageParts = explode( '.', $subpageName );
		$fileExtension = array_pop( $subpageParts );
		$subpageName = implode( '.', $subpageParts );

		for ( $index = 0; $index < count( $paths ); $index++ ) {
			if ( ( $index === count( $paths ) - 1 )
				&& $paths[$index] === $subpageName ) {
				break;
			}
			$this->appendTitleSegment( $paths[$index] );
		}

		$this->appendTitleSegment( $subpageName );

		$key = implode( ':', $this->titleSegments );

		return $key;
	}

	/**
	 *
	 * @param string $segment
	 */
	private function appendTitleSegment( $segment ) {
		$this->titleSegments[] = $segment;
	}

}
