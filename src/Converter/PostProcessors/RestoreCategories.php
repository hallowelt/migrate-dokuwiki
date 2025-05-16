<?php

namespace HalloWelt\MigrateDokuwiki\Converter\PostProcessors;

use HalloWelt\MigrateDokuwiki\IProcessor;
use HalloWelt\MigrateDokuwiki\Utility\CategoryBuilder;

class RestoreCategories implements IProcessor {

	/**
	 * @param string $text
	 * @param string $path
	 * @return string
	 */
	public function process( string $text, string $path = '' ): string {
		$text = CategoryBuilder::restoreCategories( $text );
		return $text;
	}
}
