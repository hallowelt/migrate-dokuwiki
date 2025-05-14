<?php

namespace HalloWelt\MigrateDokuwiki\Utility;

class CategoryBuilder {

	/**
	 * @param string $name
	 * @return string
	 */
	public static function getPreservedMigrationCategory( string $name ): string {
		return "#####CATEGORYOPEN#####Migration/{$name}#####CATEGORYCLOSE#####";
	}

	public static function restoreCategories( string $text ): string {
		$text = str_replace( [
			'#####CATEGORYOPEN#####',
			'#####CATEGORYCLOSE#####'
		],
		[
			'[[Category:',
			']]'
		],
		$text );

		return $text;
	}
}