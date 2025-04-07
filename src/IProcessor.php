<?php

namespace HalloWelt\MigrateDokuwiki;

interface IProcessor {

	/**
	 * @param string $text
	 * @param string $path
	 * @return string
	 */
	public function process( string $text, string $path = '' ): string;
}
