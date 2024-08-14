<?php

namespace HalloWelt\MigrateDokuwiki\Utility;

class FileKeyBuilder {

	/** @var array */
	private $titleSegments = [];

	/**
	 * @param array $paths
	 * @return string
	 */
	public function build( array $paths ) {
		$this->titleSegments = [];
		$title = $this->makeTitleKeyFromPaths( $paths );
		return $title;
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

		return "$key.$fileExtension";
	}

	/**
	 *
	 * @param string $segment
	 */
	private function appendTitleSegment( $segment ) {
		$this->titleSegments[] = $segment;
	}

}
