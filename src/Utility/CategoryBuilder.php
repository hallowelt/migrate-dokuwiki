<?php

namespace HalloWelt\MigrateDokuwiki\Utility;

class CategoryBuilder {

	/**
	 * @param string $name
	 * @return string
	 */
	public static function getMigrationCategory( string $name ): string {
		return "[[Category:Migration/{$name}]]";
	}
}