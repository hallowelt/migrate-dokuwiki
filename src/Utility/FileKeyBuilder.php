<?php

namespace HalloWelt\MigrateDokuwiki\Utility;

class FileKeyBuilder {

	/** @var array */
	private $titleSegments = [];

	/** @var string */
	private $fileExtension = '';

	/**
	 * @param array $paths
	 * @return string
	 */
	public function build( array $paths ): string {
		$this->makeTitleKeyFromPaths( $paths );

		$key = implode( ':', $this->titleSegments );

		return "{$key}.{$this->fileExtension}";
	}

	/**
	 * @param array $paths
	 * @return string
	 */
	public function buildDoubleKey( array $paths ): string {
		$this->makeTitleKeyFromPaths( $paths );

		$doubleKey = end( $this->titleSegments );
		$this->titleSegments[] = $doubleKey;

		$key = implode( ':', $this->titleSegments );

		return "{$key}.{$this->fileExtension}";
	}

	/**
	 * @param array $paths
	 * @return void
	 */
	private function makeTitleKeyFromPaths( array $paths ): void {
		$this->titleSegments = [];

		$subpageName = array_pop( $paths );
		$subpageParts = explode( '.', $subpageName );
		$this->fileExtension = array_pop( $subpageParts );
		$subpageName = implode( '.', $subpageParts );

		for ( $index = 0; $index < count( $paths ); $index++ ) {
			if ( ( $index === count( $paths ) - 1 )
				&& $paths[$index] === $subpageName ) {
				break;
			}
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
	private function appendTitleSegment( string $segment ): void {
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
