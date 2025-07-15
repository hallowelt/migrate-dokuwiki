<?php

namespace HalloWelt\MigrateDokuwiki\Converter\PostProcessors;

use HalloWelt\MigrateDokuwiki\IProcessor;

class RestoreInclude implements IProcessor {

	/**
	 * @param string $text
	 * @param string $path
	 * @return string
	 */
	public function process( string $text, string $path = '' ): string {
		$text = str_replace(
			[
				'#####PRESERVEINCLUDEOPEN#####',
				'#####PRESERVEINCLUDEPIPE#####',
				'#####PRESERVEINCLUDECLOSE#####'
			],
			[
				'{{Include ',
				'|',
				'}}'
			],
			$text
		);
		return $text;
	}
}
