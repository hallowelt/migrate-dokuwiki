<?php

namespace HalloWelt\MigrateDokuwiki\Utility;

trait MaskWikiLinksTrait {

	/**
	 * @var array
	 */
	private $maskedInternalLinks = [];

	/**
	 * @param array $lines
	 * @return array
	 */
	private function maskInternalLinks( array $lines ): array {
		$counter = 1;
		$maskedInternalLinks = [];

		foreach ( $lines as &$line ) {
			$internalLinksPattern = "/\[\[.*?]]/";

			$line = preg_replace_callback(
				$internalLinksPattern,
				static function ( $matches ) use ( &$maskedInternalLinks, &$counter ) {
					$replacement = "###masked_link_$counter###";

					$maskedInternalLinks[$replacement] = $matches[0];

					$counter++;

					return $replacement;
				},
				$line
			);
		}
		unset( $line );

		$this->maskedInternalLinks = $maskedInternalLinks;

		return $lines;
	}

	/**
	 * @param array $lines
	 * @return array
	 */
	private function unmaskInternalLinks( array $lines ): array {
		foreach ( $lines as &$line ) {
			$internalLinksPattern = "/###masked_link_.*?###/";

			$maskedInternalLinks = $this->maskedInternalLinks;

			$line = preg_replace_callback(
				$internalLinksPattern,
				static function ( $matches ) use ( $maskedInternalLinks ) {
					$originalLink = $maskedInternalLinks[ $matches[0] ];

					return $originalLink;
				},
				$line
			);
		}
		unset( $line );

		return $lines;
	}
}
