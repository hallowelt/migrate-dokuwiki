<?php

namespace HalloWelt\MigrateDokuwiki;

interface IExtractor {

	/**
	 * @return bool
	 */
	public function extract(): bool;
}
