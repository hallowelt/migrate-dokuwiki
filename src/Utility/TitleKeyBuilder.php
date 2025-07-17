<?php

namespace HalloWelt\MigrateDokuwiki\Utility;

class TitleKeyBuilder {

	/** @var array */
	private $titleSegments = [];

	/**
	 * @param array $paths
	 * @return string
	 */
	public function build( array $paths ): string {
		$this->makeTitleKeyFromPaths( $paths );

		$reverse = array_reverse( $this->titleSegments );
		if ( isset( $reverse[2] ) && $reverse[0] === $reverse[1] ) {
			// some dokuwiki have the subpage content inside the directory,
			// ohters inside the parent directory. The first case prduces a double
			// key which creates a double title part.
			array_pop( $this->titleSegments );
		}

		$key = implode( ':', $this->titleSegments );
		return $key;
	}

	/**
	 * @param array $paths
	 * @return void
	 */
	private function makeTitleKeyFromPaths( array $paths ): void {
		$this->titleSegments = [];

		$subpageName = array_pop( $paths );
		$subpageParts = explode( '.', $subpageName );
		$fileExtension = array_pop( $subpageParts );
		$subpageName = implode( '.', $subpageParts );

		for ( $index = 0; $index < count( $paths ); $index++ ) {
			$path = $paths[$index];
			$path = $this->generalizeItem( $path );

			$this->appendTitleSegment( $path );
		}

		$subpageName = $this->generalizeItem( $subpageName );
		$this->appendTitleSegment( $subpageName );
	}

	/**
	 * @param string $segment
	 * @return void
	 */
	private function appendTitleSegment( $segment ): void {
		$this->titleSegments[] = $segment;
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
}
