<?php

namespace HalloWelt\MigrateDokuwiki;

interface IProcessor {

	/**
	 * @param string $text
	 * @return string
	 */
	public function process( string $text ): string;
}
